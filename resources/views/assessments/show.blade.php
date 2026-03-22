<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    CareerBuddy Assessment
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Question {{ $index + 1 }} of {{ $total }} · Answered {{ $answered }} / {{ $total }}
                </p>
            </div>

            <form method="POST" action="{{ route('assessments.submit', $assessment) }}">
                @csrf
                <x-primary-button type="submit">
                    Submit
                </x-primary-button>
            </form>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-yellow-50 p-4 text-yellow-800 border border-yellow-200">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">
                            Category: {{ ucfirst($response->question->category) }}
                        </div>
                    </div>

                    <div class="mt-4 text-lg font-semibold text-gray-900">
                        {{ $response->question->question_text }}
                    </div>

                    <form method="POST" action="{{ route('assessments.answer', [$assessment, $index]) }}" class="mt-6 space-y-3">
                        @csrf

                        @php
                            $selected = old('selected_option', $response->selected_option);
                            $options = [
                                'A' => $response->question->option_a,
                                'B' => $response->question->option_b,
                                'C' => $response->question->option_c,
                                'D' => $response->question->option_d,
                            ];
                        @endphp

                        @foreach ($options as $key => $label)
                            <label class="flex items-start gap-3 rounded-lg border p-4 cursor-pointer hover:bg-gray-50 transition">
                                <input
                                    type="radio"
                                    name="selected_option"
                                    value="{{ $key }}"
                                    class="mt-1"
                                    @checked($selected === $key)
                                    required
                                />
                                <div>
                                    <div class="font-semibold text-gray-900">{{ $key }}.</div>
                                    <div class="text-gray-700">{{ $label }}</div>
                                </div>
                            </label>
                        @endforeach

                        @error('selected_option')
                            <div class="text-sm text-red-600">{{ $message }}</div>
                        @enderror

                        <div class="flex items-center justify-between pt-2">
                            <div class="text-sm text-gray-500">
                                Tip: you can submit at any time, but all questions must be answered.
                            </div>

                            <x-primary-button type="submit">
                                {{ $isLast ? 'Save' : 'Save & Next' }}
                            </x-primary-button>
                        </div>
                    </form>

                    <div class="mt-8">
                        <div class="h-2 w-full bg-gray-200 rounded-full overflow-hidden">
                            @php
                                $pct = $total > 0 ? (int) round(($answered / $total) * 100) : 0;
                            @endphp
                            <div class="h-2 bg-indigo-600" style="width: {{ $pct }}%"></div>
                        </div>
                        <div class="mt-2 text-xs text-gray-500">
                            Progress: {{ $pct }}%
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-between">
                <a
                    href="{{ route('assessments.show', [$assessment, max(0, $index - 1)]) }}"
                    class="text-sm text-gray-600 hover:text-gray-900"
                >
                    ← Previous
                </a>

                <a
                    href="{{ route('assessments.show', [$assessment, min($total - 1, $index + 1)]) }}"
                    class="text-sm text-gray-600 hover:text-gray-900"
                >
                    Next →
                </a>
            </div>
        </div>
    </div>
</x-app-layout>

