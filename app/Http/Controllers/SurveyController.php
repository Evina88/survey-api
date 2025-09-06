<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    // GET /api/surveys (public) — list active surveys
    public function index()
    {
        $surveys = Survey::active()
            ->select('id','title','description','status','created_at','updated_at')
            ->orderBy('id','desc')
            ->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'Active surveys',
            'data'    => $surveys,
        ]);
    }

    // GET /api/surveys/{id} (public) — details with questions
    public function show($id)
    {
        $survey = Survey::active()
            ->with(['questions:id,survey_id,type,question_text,created_at,updated_at'])
            ->find($id);

        if (!$survey) {
            return response()->json([
                'status'  => 'not_found',
                'message' => 'Survey not found or inactive.',
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Survey details',
            'data'    => $survey,
        ]);
    }
}
