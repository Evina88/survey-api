<?php

namespace App\Http\Controllers;

use App\Jobs\LogSurveySubmissionToElastic;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SurveySubmissionController extends Controller
{
    // POST /api/surveys/{id}/submit  (auth:api)
    public function submit(Request $request, int $id)
    {
        $survey = Survey::active()->find($id);
        if (!$survey) {
            return response()->json([
                'status'  => 'not_found',
                'message' => 'Survey not found or inactive.',
                'data'    => null,
            ], 404);
        }

        $data = $request->validate([
            'responses'               => 'required|array|min:1',
            'responses.*.question_id' => 'required|integer',
            'responses.*.value'       => 'required',
        ]);

        // Authenticated responder
        $responderId = auth('api')->id();
        if (!$responderId) {
            return response()->json([
                'status'  => 'unauthorized',
                'message' => 'Authentication required.',
                'data'    => null,
            ], 401);
        }

        // Fetch survey questions once
        $questions = Question::where('survey_id', $survey->id)->get()->keyBy('id');

        $normalized = [];

        foreach ($data['responses'] as $idx => $resp) {
            $qid = (int) $resp['question_id'];
            $val = $resp['value'];

            $q = $questions->get($qid);
            if (!$q) {
                throw ValidationException::withMessages([
                    "responses.$idx.question_id" => ["Question $qid does not belong to this survey."],
                ]);
            }

            switch ($q->type) {
                case 'text':
                    if (!is_string($val) || trim($val) === '' || mb_strlen($val) > 2000) {
                        throw ValidationException::withMessages([
                            "responses.$idx.value" => ['Text answer must be a non-empty string up to 2000 characters.'],
                        ]);
                    }
                    $normalized[] = [
                        'question_id'   => $qid,
                        'responder_id'  => $responderId,
                        'response_data' => ['type' => 'text', 'value' => $val],
                    ];
                    break;

                case 'scale':
                    // 1..5 scale for this assessment
                    if (!is_int($val)) {
                        if (is_string($val) && ctype_digit($val)) {
                            $val = (int) $val;
                        } else {
                            throw ValidationException::withMessages([
                                "responses.$idx.value" => ['Scale answer must be an integer between 1 and 5.'],
                            ]);
                        }
                    }
                    if ($val < 1 || $val > 5) {
                        throw ValidationException::withMessages([
                            "responses.$idx.value" => ['Scale answer must be an integer between 1 and 5.'],
                        ]);
                    }
                    $normalized[] = [
                        'question_id'   => $qid,
                        'responder_id'  => $responderId,
                        'response_data' => ['type' => 'scale', 'value' => $val, 'range' => [1, 5]],
                    ];
                    break;

                case 'multiple_choice':
                    // Seeded choices: Yes/No
                    if (is_string($val)) {
                        $val = trim($val);
                    }
                    $allowed = ['Yes', 'No'];
                    if (!in_array($val, $allowed, true)) {
                        throw ValidationException::withMessages([
                            "responses.$idx.value" => ['Multiple choice must be one of: Yes, No.'],
                        ]);
                    }
                    $normalized[] = [
                        'question_id'   => $qid,
                        'responder_id'  => $responderId,
                        'response_data' => ['type' => 'multiple_choice', 'value' => $val, 'options' => $allowed],
                    ];
                    break;

                default:
                    throw ValidationException::withMessages([
                        "responses.$idx.question_id" => ["Unsupported question type: {$q->type}."],
                    ]);
            }
        }

        // Persist answers atomically
        DB::transaction(function () use ($normalized) {
            foreach ($normalized as $row) {
                Answer::create($row);
            }
        });

        // Fire-and-forget ES logging (bonus)
        dispatch(new LogSurveySubmissionToElastic([
            'survey_id'    => $survey->id,
            'responder_id' => $responderId,
            'submitted_at' => now()->toISOString(),
            'ip'           => $request->ip(),
            'user_agent'   => (string) $request->userAgent(),
            'answers'      => $normalized,
        ]));

        return response()->json([
            'status'  => 'success',
            'message' => 'Survey submitted successfully.',
            'data'    => [
                'survey_id' => $survey->id,
                'answers'   => $normalized,
            ],
        ], 201);
    }
}
