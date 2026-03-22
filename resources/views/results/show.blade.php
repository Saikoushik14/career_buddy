<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Your CareerBuddy Results
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Total Score: {{ $result->total_score }} · Recommended: {{ $result->recommended_career }}
                </p>
            </div>
            <a href="{{ route('dashboard') }}" class="text-sm text-indigo-600 hover:text-indigo-800">
                Back to dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900">Category Scores</h3>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        @foreach (($result->category_scores ?? []) as $category => $score)
                            <div class="rounded-lg border p-4">
                                <div class="text-sm text-gray-500 uppercase tracking-wide">{{ $category }}</div>
                                <div class="mt-2 text-3xl font-bold text-gray-900">{{ $score }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900">Skill Gap Analysis</h3>
                    <ul class="mt-3 list-disc ms-6 text-gray-700">
                        @foreach (($result->skill_gaps ?? []) as $gap)
                            <li>{{ $gap }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900">Roadmap</h3>

                    <div class="mt-4 space-y-4">
                        @foreach (($result->roadmap ?? []) as $step)
                            <div class="rounded-lg border p-4">
                                <div class="font-semibold text-gray-900">
                                    Step {{ $step['step'] ?? '' }}: {{ $step['title'] ?? '' }}
                                </div>
                                <ul class="mt-2 list-disc ms-6 text-gray-700">
                                    @foreach (($step['items'] ?? []) as $item)
                                        <li>{{ $item }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

