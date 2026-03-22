<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('questions:import {path : Absolute or relative path to the CSV file}', function (string $path) {
    $fullPath = $path;
    if (!str_starts_with($path, DIRECTORY_SEPARATOR) && !preg_match('/^[A-Za-z]:\\\\/', $path)) {
        $fullPath = base_path($path);
    }

    if (!is_file($fullPath)) {
        $this->error("File not found: {$fullPath}");
        return 1;
    }

    $this->info("Importing questions from: {$fullPath}");

    $allowedCategories = ['aptitude', 'personality', 'technical'];

    $rows = LazyCollection::make(function () use ($fullPath) {
        $handle = fopen($fullPath, 'r');
        if ($handle === false) {
            return;
        }
        try {
            while (($data = fgetcsv($handle)) !== false) {
                yield $data;
            }
        } finally {
            fclose($handle);
        }
    });

    $header = null;
    $imported = 0;
    $skipped = 0;

    $now = now();
    $buffer = [];

    $flush = function () use (&$buffer, &$imported) {
        if (count($buffer) === 0) {
            return;
        }
        DB::table('questions')->insert($buffer);
        $imported += count($buffer);
        $buffer = [];
    };

    foreach ($rows as $i => $data) {
        if ($i === 0) {
            $header = array_map(fn ($h) => strtolower(trim((string) $h)), $data);
            // If the first row doesn't look like a header, treat it as data with the expected order.
            $expected = ['question_text', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer', 'category'];
            $looksLikeHeader = count(array_intersect($expected, $header)) >= 3;
            if ($looksLikeHeader) {
                continue;
            }
            $header = $expected;
        }

        $row = [];
        foreach ($header as $idx => $key) {
            $row[$key] = $data[$idx] ?? null;
        }

        $questionText = trim((string) ($row['question_text'] ?? ''));
        $a = trim((string) ($row['option_a'] ?? ''));
        $b = trim((string) ($row['option_b'] ?? ''));
        $c = trim((string) ($row['option_c'] ?? ''));
        $d = trim((string) ($row['option_d'] ?? ''));
        $category = strtolower(trim((string) ($row['category'] ?? '')));
        $correct = $row['correct_answer'] ?? null;
        $correct = is_string($correct) ? strtoupper(trim($correct)) : null;
        $correct = in_array($correct, ['A', 'B', 'C', 'D'], true) ? $correct : null;

        if ($questionText === '' || $a === '' || $b === '' || $c === '' || $d === '') {
            $skipped += 1;
            continue;
        }

        if (!in_array($category, $allowedCategories, true)) {
            $category = 'aptitude';
        }

        $buffer[] = [
            'question_text' => $questionText,
            'option_a' => $a,
            'option_b' => $b,
            'option_c' => $c,
            'option_d' => $d,
            'correct_answer' => $correct,
            'category' => $category,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (count($buffer) >= 1000) {
            $flush();
            $this->info("Imported {$imported}...");
        }
    }

    $flush();

    $this->info("Done. Imported: {$imported}, Skipped: {$skipped}");
    return 0;
})->purpose('Import questions from a CSV file into the database');
