<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    public function show(Request $request, Assessment $assessment)
    {
        abort_unless($assessment->user_id === $request->user()->id, 403);

        $assessment->load('result');

        if (!$assessment->result) {
            return redirect()->route('assessments.show', [$assessment, 0])
                ->with('status', 'Complete and submit the assessment to view results.');
        }

        return view('results.show', [
            'assessment' => $assessment,
            'result' => $assessment->result,
        ]);
    }
}

