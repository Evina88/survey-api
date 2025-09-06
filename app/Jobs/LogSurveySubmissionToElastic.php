<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LogSurveySubmissionToElastic implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $doc;

    public function __construct(array $doc)
    {
        $this->doc = $doc;
    }

    public function handle(): void
    {
        $cfg = config('services.elasticsearch');

        // Basic config sanity
        if (empty($cfg['enabled']) || empty($cfg['host']) || empty($cfg['index'])) {
            Log::info('elastic.disabled_or_unconfigured', ['cfg' => [
                'enabled' => $cfg['enabled'] ?? null,
                'host'    => $cfg['host'] ?? null,
                'index'   => $cfg['index'] ?? null,
            ]]);
            return;
        }

        $host   = rtrim((string) $cfg['host'], '/');
        $index  = (string) $cfg['index'];
        $timeout = (int) ($cfg['timeout'] ?? 3);

        $indexUrl = $host . '/' . urlencode($index);
        $docUrl   = $indexUrl . '/_doc';

        $client = Http::timeout($timeout)->withHeaders([
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ]);

        if (!empty($cfg['username']) && !empty($cfg['password'])) {
            $client = $client->withBasicAuth($cfg['username'], $cfg['password']);
        }

        // ------- Attempt 1: index the document --------
        Log::info('elastic.debug.request', ['url' => $docUrl, 'doc' => $this->doc]);

        try {
            $resp = $client->post($docUrl, $this->doc);
        } catch (\Throwable $e) {
            Log::warning('elastic.request_exception', ['step' => 'post_doc_initial', 'error' => $e->getMessage()]);
            return;
        }

        Log::info('elastic.debug.response', [
            'step'   => 'post_doc_initial',
            'status' => $resp->status(),
            'body'   => mb_substr((string) $resp->body(), 0, 600),
        ]);

        if ($resp->successful()) {
            return; // done
        }

        // If index not found, try to create it once, then retry insert
        $isIndexNotFound = $resp->status() === 404 && str_contains((string) $resp->body(), 'index_not_found_exception');
        if ($isIndexNotFound) {
            Log::info('elastic.index_missing_attempt_create', ['index' => $index]);

            $createPayload = [
                'settings' => [
                    'number_of_shards'   => 1,
                    'number_of_replicas' => 0,
                ],
                'mappings' => [
                    'properties' => [
                        'survey_id'    => ['type' => 'integer'],
                        'responder_id' => ['type' => 'integer'],
                        'submitted_at' => ['type' => 'date'],
                        'ip'           => ['type' => 'ip'],
                        'user_agent'   => ['type' => 'keyword'],
                        'answers'      => [
                            'type'       => 'nested',
                            'properties' => [
                                'question_id'   => ['type' => 'integer'],
                                'responder_id'  => ['type' => 'integer'],
                                'response_data' => ['type' => 'object', 'enabled' => true],
                            ],
                        ],
                    ],
                ],
            ];

            try {
                $create = $client->put($indexUrl, $createPayload);
                Log::info('elastic.debug.response', [
                    'step'   => 'create_index',
                    'status' => $create->status(),
                    'body'   => mb_substr((string) $create->body(), 0, 600),
                ]);
            } catch (\Throwable $e) {
                Log::warning('elastic.request_exception', ['step' => 'create_index', 'error' => $e->getMessage()]);
                return;
            }

            if ($create->successful()) {
                // Retry posting the doc once
                try {
                    $retry = $client->post($docUrl, $this->doc);
                    Log::info('elastic.debug.response', [
                        'step'   => 'post_doc_retry',
                        'status' => $retry->status(),
                        'body'   => mb_substr((string) $retry->body(), 0, 600),
                    ]);

                    if (!$retry->successful()) {
                        Log::warning('elastic.index_failed_after_retry', [
                            'status' => $retry->status(),
                            'body'   => mb_substr((string) $retry->body(), 0, 600),
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('elastic.request_exception', ['step' => 'post_doc_retry', 'error' => $e->getMessage()]);
                }
            } else {
                Log::warning('elastic.index_create_failed', [
                    'status' => $create->status(),
                    'body'   => mb_substr((string) $create->body(), 0, 600),
                ]);
            }

            return;
        }

        // Common misconfig: security enabled, needs auth
        if (in_array($resp->status(), [401, 403], true)) {
            Log::warning('elastic.auth_required_or_forbidden', [
                'status' => $resp->status(),
                'hint'   => 'Disable security for local dev (xpack.security.enabled=false) or set ELASTICSEARCH_USERNAME/PASSWORD.',
            ]);
            return;
        }

        // Otherwise, log generic failure
        Log::warning('elastic.index_failed', [
            'status' => $resp->status(),
            'body'   => mb_substr((string) $resp->body(), 0, 600),
        ]);
    }
}
