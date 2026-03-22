<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class CareerPredictionService
{
    /**
     * @param array{aptitude:int, personality:int, technical:int} $categoryScores
     * @return array{recommended_career:string, skill_gaps:array, roadmap:array}
     */
    public function predict(array $categoryScores): array
    {
        $provider = config('ai.provider', 'openai');
        $enabled = (bool) config('ai.enabled', false);

        if (!$enabled) {
            return $this->fallback($categoryScores);
        }

        if ($provider !== 'openai') {
            return $this->fallback($categoryScores);
        }

        $apiKey = (string) config('ai.openai.api_key');
        if ($apiKey === '') {
            return $this->fallback($categoryScores);
        }

        $baseUrl = rtrim((string) config('ai.openai.base_url'), '/');
        $model = (string) config('ai.openai.model', 'gpt-4.1-mini');
        $timeoutSeconds = (int) config('ai.openai.timeout_seconds', 20);

        $topCategory = $this->topCategory($categoryScores);

        $prompt = $this->buildPrompt($categoryScores, $topCategory);

        try {
            $response = Http::timeout($timeoutSeconds)
                ->withToken($apiKey)
                ->acceptJson()
                ->post($baseUrl . '/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a career advisor. Output ONLY valid JSON.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.2,
                    'response_format' => ['type' => 'json_object'],
                ]);

            if (!$response->successful()) {
                return $this->fallback($categoryScores);
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;
            if (!is_string($content) || $content === '') {
                return $this->fallback($categoryScores);
            }

            $json = json_decode($content, true);
            if (!is_array($json)) {
                return $this->fallback($categoryScores);
            }

            $recommendedCareer = (string) ($json['recommended_career'] ?? '');
            $skillGaps = $json['skill_gaps'] ?? [];
            $roadmap = $json['roadmap'] ?? [];

            $skillGaps = is_array($skillGaps) ? $skillGaps : [];
            $roadmap = is_array($roadmap) ? $roadmap : [];

            if ($recommendedCareer === '') {
                return $this->fallback($categoryScores);
            }

            return [
                'recommended_career' => $recommendedCareer,
                'skill_gaps' => $skillGaps,
                'roadmap' => $roadmap,
            ];
        } catch (Throwable) {
            return $this->fallback($categoryScores);
        }
    }

    /**
     * @param array{aptitude:int, personality:int, technical:int} $categoryScores
     * @return array{recommended_career:string, skill_gaps:array, roadmap:array}
     */
    private function fallback(array $categoryScores): array
    {
        arsort($categoryScores);
        $top = $this->topCategory($categoryScores);

        if ($top === 'technical') {
            $recommendedCareer = 'Software Engineering';
            $skillGaps = ['Data structures', 'System design', 'Testing', 'Problem solving'];
            $roadmap = [
                ['step' => 1, 'title' => 'Strengthen fundamentals', 'items' => ['DSA practice', 'OOP', 'Git']],
                ['step' => 2, 'title' => 'Build projects', 'items' => ['REST APIs', 'Auth', 'CRUD']],
                ['step' => 3, 'title' => 'Prepare for interviews', 'items' => ['Mock interviews', 'System design basics']],
            ];
        } elseif ($top === 'personality') {
            $recommendedCareer = 'Web Development';
            $skillGaps = ['HTTP fundamentals', 'Frontend basics', 'Deployment', 'Problem solving'];
            $roadmap = [
                ['step' => 1, 'title' => 'Web foundations', 'items' => ['HTML/CSS', 'JavaScript', 'HTTP']],
                ['step' => 2, 'title' => 'Backend + database', 'items' => ['Laravel basics', 'MySQL', 'Security']],
                ['step' => 3, 'title' => 'Ship projects', 'items' => ['Deploy', 'CI/CD basics', 'Portfolio']],
            ];
        } else {
            $recommendedCareer = 'Data Science';
            $skillGaps = ['Python', 'Statistics', 'ML basics'];
            $roadmap = [
                ['step' => 1, 'title' => 'Core skills', 'items' => ['Python', 'Pandas', 'SQL']],
                ['step' => 2, 'title' => 'Analytics', 'items' => ['Statistics', 'EDA', 'Visualization']],
                ['step' => 3, 'title' => 'ML introduction', 'items' => ['Supervised learning', 'Model evaluation']],
            ];
        }

        return [
            'recommended_career' => $recommendedCareer,
            'skill_gaps' => $skillGaps,
            'roadmap' => $roadmap,
        ];
    }

    /**
     * @param array{aptitude:int, personality:int, technical:int} $categoryScores
     */
    private function topCategory(array $categoryScores): string
    {
        arsort($categoryScores);
        $top = array_key_first($categoryScores);
        return is_string($top) && $top !== '' ? $top : 'aptitude';
    }

    /**
     * @param array{aptitude:int, personality:int, technical:int} $categoryScores
     */
    private function buildPrompt(array $categoryScores, string $topCategory): string
    {
        $allowedCareers = [
            'Data Science',
            'Web Development',
            'Cybersecurity',
            'AI/ML',
            'Software Engineering',
        ];

        $allowedCareersText = implode(', ', $allowedCareers);

        return <<<PROMPT
Given a user's assessment scores across categories, recommend a career and generate a personalized skill gap analysis and a 3-step roadmap.

Scores (out of 100 across your selected 100 questions):
- aptitude: {$categoryScores['aptitude']}
- personality: {$categoryScores['personality']}
- technical: {$categoryScores['technical']}

Top category: {$topCategory}

Return ONLY valid JSON with this exact shape:
{
  "recommended_career": one of: "{$allowedCareersText}",
  "skill_gaps": [string, string, ...],
  "roadmap": [
    { "step": 1, "title": string, "items": [string, string, ...] },
    { "step": 2, "title": string, "items": [string, string, ...] },
    { "step": 3, "title": string, "items": [string, string, ...] }
  ]
}
PROMPT;
    }
}

