<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportQuestions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-questions {--file=storage/app/questions.csv : The path to the CSV file}';

    protected $description = 'Import questions from a CSV file into the database';

    public function handle()
    {
        $filePath = base_path($this->option('file'));

        if (!file_exists($filePath)) {
            $this->error("File not found at: {$filePath}");
            return Command::FAILURE;
        }

        $this->info("Importing questions from {$filePath}...");

        $file = fopen($filePath, 'r');
        // Get headers
        $headers = fgetcsv($file);
        
        $count = 0;
        $batch = [];
        $batchSize = 1000;

        $this->output->progressStart();

        while (($row = fgetcsv($file)) !== false) {
            $data = array_combine($headers, $row);
            
            $batch[] = [
                'question_text' => $data['question_text'] ?? '',
                'option_a' => $data['option_a'] ?? '',
                'option_b' => $data['option_b'] ?? '',
                'option_c' => $data['option_c'] ?? '',
                'option_d' => $data['option_d'] ?? '',
                'correct_answer' => $data['correct_answer'] ?? '',
                'category' => $data['category'] ?? 'aptitude',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= $batchSize) {
                \App\Models\Question::insert($batch);
                $batch = [];
            }
            
            $count++;
            if ($count % 500 == 0) {
                $this->output->progressAdvance(500);
            }
        }

        if (!empty($batch)) {
            \App\Models\Question::insert($batch);
        }

        fclose($file);
        $this->output->progressFinish();
        $this->info("\nSuccessfully imported {$count} questions!");

        return Command::SUCCESS;
    }
}
