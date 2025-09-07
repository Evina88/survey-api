<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SurveyController extends Controller
{
    /**
     * GET /api/surveys (public)
     * Returns all active surveys. Cached in Redis for SURVEY_CACHE_TTL seconds.
     */
    public function index()
    {
        $ttl = (int) env('SURVEY_CACHE_TTL', 300);
        $cacheKey = 'surveys:active';

        $surveys = Cache::remember($cacheKey, $ttl, function () {
            return Survey::query()
                ->where('status', 'active')
                ->select(['id', 'title', 'description', 'status', 'created_at', 'updated_at'])
                ->orderBy('id')
                ->get();
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Active surveys',
            'data'    => $surveys,
        ]);
    }

    /**
     * GET /api/surveys/{id} (public)
     * Returns survey details with questions. Cached per-survey.
     */
    public function show($id)
    {
        $ttl = (int) env('SURVEY_CACHE_TTL', 300);
        $cacheKey = "surveys:show:{$id}";

        $payload = Cache::remember($cacheKey, $ttl, function () use ($id) {
            $survey = Survey::query()
                ->where('status', 'active')
                ->find($id);

            if (!$survey) {
                return null;
            }

            // lazy load questions only when needed
            $survey->load(['questions:id,survey_id,type,question_text,created_at,updated_at']);

            return [
                'id'          => $survey->id,
                'title'       => $survey->title,
                'description' => $survey->description,
                'status'      => $survey->status,
                'created_at'  => $survey->created_at,
                'updated_at'  => $survey->updated_at,
                'questions'   => $survey->questions,
            ];
        });

        if (!$payload) {
            return response()->json([
                'status'  => 'not_found',
                'message' => 'Survey not found or inactive.',
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Survey details',
            'data'    => $payload,
        ]);
    }
}
