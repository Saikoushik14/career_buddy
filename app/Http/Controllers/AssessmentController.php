<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Question;
use App\Models\Response;
use App\Models\Result;
use App\Services\CareerPredictionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AssessmentController extends Controller
{
    public function start(Request $request)
    {
        $user = $request->user();

        $existing = Assessment::query()
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->latest('id')
            ->first();

        if ($existing) {
            $firstUnansweredIndex = (int) Response::query()
                ->where('assessment_id', $existing->id)
                ->orderBy('id')
                ->pluck('selected_option')
                ->search(null);

            $index = $firstUnansweredIndex === -1 ? 0 : $firstUnansweredIndex;

            return redirect()->route('assessments.show', [$existing, $index]);
        }

        $assessment = null;

        DB::transaction(function () use ($user, &$assessment) {
            $assessment = Assessment::create([
                'user_id' => $user->id,
                'status' => 'in_progress',
            ]);

            $questionIds = Question::query()
                ->inRandomOrder()
                ->limit(100)
                ->pluck('id')
                ->all();

            $now = Carbon::now();
            $rows = array_map(
                fn ($qid) => [
                    'assessment_id' => $assessment->id,
                    'question_id' => $qid,
                    'selected_option' => null,
                    'is_correct' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                $questionIds
            );

            Response::query()->insert($rows);
        });

        return redirect()->route('assessments.show', [$assessment, 0]);
    }

    public function show(Request $request, Assessment $assessment, int $index)
    {
        $this->authorizeAssessment($request, $assessment);

        $total = Response::query()->where('assessment_id', $assessment->id)->count();
        if ($total === 0) {
            return redirect()->route('assessments.start');
        }

        if ($index < 0 || $index >= $total) {
            return redirect()->route('assessments.show', [$assessment, 0]);
        }

        $response = Response::query()
            ->where('assessment_id', $assessment->id)
            ->orderBy('id')
            ->with('question')
            ->skip($index)
            ->firstOrFail();

        $answered = Response::query()
            ->where('assessment_id', $assessment->id)
            ->whereNotNull('selected_option')
            ->count();

        return view('assessments.show', [
            'assessment' => $assessment,
            'response' => $response,
            'index' => $index,
            'total' => $total,
            'answered' => $answered,
            'isLast' => $index === ($total - 1),
        ]);
    }

    public function answer(Request $request, Assessment $assessment, int $index)
    {
        $this->authorizeAssessment($request, $assessment);

        if ($assessment->status !== 'in_progress') {
            return redirect()->route('results.show', $assessment);
        }

        $validated = $request->validate([
            'selected_option' => ['required', 'in:A,B,C,D'],
        ]);

        $response = Response::query()
            ->where('assessment_id', $assessment->id)
            ->orderBy('id')
            ->with('question')
            ->skip($index)
            ->firstOrFail();

        $selected = $validated['selected_option'];
        $correct = $response->question?->correct_answer;
        $isCorrect = null;

        if (is_string($correct) && $correct !== '') {
            $normalized = strtoupper(trim($correct));
            $isCorrect = $normalized === strtoupper($selected);
        }

        $response->update([
            'selected_option' => $selected,
            'is_correct' => $isCorrect,
        ]);

        $nextIndex = $index + 1;
        $total = Response::query()->where('assessment_id', $assessment->id)->count();

        if ($nextIndex < $total) {
            return redirect()->route('assessments.show', [$assessment, $nextIndex]);
        }

        return redirect()->route('assessments.show', [$assessment, $index]);
    }

    public function submit(Request $request, Assessment $assessment)
    {
        $this->authorizeAssessment($request, $assessment);

        if ($assessment->status !== 'in_progress') {
            return redirect()->route('results.show', $assessment);
        }

        $unanswered = Response::query()
            ->where('assessment_id', $assessment->id)
            ->whereNull('selected_option')
            ->count();

        if ($unanswered > 0) {
            return redirect()
                ->route('assessments.show', [$assessment, 0])
                ->with('status', "Please answer all questions before submitting. ($unanswered remaining)");
        }

        $responses = Response::query()
            ->where('assessment_id', $assessment->id)
            ->with('question:id,category,correct_answer')
            ->get();

        $categoryScores = [
            'aptitude' => 0,
            'personality' => 0,
            'technical' => 0,
        ];

        $totalScore = 0;

        foreach ($responses as $r) {
            $cat = $r->question?->category ?? 'aptitude';
            $cat = array_key_exists($cat, $categoryScores) ? $cat : 'aptitude';

            $isCorrect = $r->is_correct === true;
            if ($isCorrect) {
                $totalScore += 1;
                $categoryScores[$cat] += 1;
            }
        }

        // Prefer AI-based recommendation when enabled; otherwise uses fallback heuristics.
        $prediction = app(CareerPredictionService::class)->predict($categoryScores);
        $recommendedCareer = $prediction['recommended_career'] ?? 'Data Science';
        $skillGaps = $prediction['skill_gaps'] ?? [];
        $roadmap = $prediction['roadmap'] ?? [];

        DB::transaction(function () use ($assessment, $totalScore, $categoryScores, $recommendedCareer, $skillGaps, $roadmap) {
            $assessment->update([
                'status' => 'completed',
                'score' => $totalScore,
            ]);

            Result::query()->updateOrCreate(
                ['assessment_id' => $assessment->id],
                [
                    'user_id' => $assessment->user_id,
                    'total_score' => $totalScore,
                    'category_scores' => $categoryScores,
                    'recommended_career' => $recommendedCareer,
                    'skill_gaps' => $skillGaps,
                    'roadmap' => $roadmap,
                ]
            );
        });

        return redirect()->route('results.show', $assessment);
    }

    private function authorizeAssessment(Request $request, Assessment $assessment): void
    {
        abort_unless($assessment->user_id === $request->user()->id, 403);
    }

    // NOTE: legacy heuristic methods were moved into CareerPredictionService fallback()
}

