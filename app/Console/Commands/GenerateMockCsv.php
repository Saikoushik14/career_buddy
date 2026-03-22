<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateMockCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-mock-csv {--count=15000 : The number of questions to generate}';

    protected $description = 'Generate a mock CSV file with career assessment questions';

    public function handle()
    {
        $count = (int) $this->option('count');
        $filePath = storage_path('app/questions.csv');
        
        $this->info("Generating {$count} mock questions into {$filePath}...");

        $file = fopen($filePath, 'w');
        
        // Write headers
        fputcsv($file, [
            'question_text',
            'option_a',
            'option_b',
            'option_c',
            'option_d',
            'correct_answer',
            'category'
        ]);

        $categories = ['aptitude', 'personality', 'technical'];
        $options = ['A', 'B', 'C', 'D'];
        
        $this->output->progressStart($count);

        for ($i = 1; $i <= $count; $i++) {
            $category = $categories[array_rand($categories)];
            
            $question = [
                'question_text' => "Sample {$category} question {$i}? Select the best option.",
                'option_a' => "Option A for question {$i}",
                'option_b' => "Option B for question {$i}",
                'option_c' => "Option C for question {$i}",
                'option_d' => "Option D for question {$i}",
                'correct_answer' => $category !== 'personality' ? $options[array_rand($options)] : '',
                'category' => $category,
            ];

            fputcsv($file, $question);
            
            if ($i % 500 === 0) {
                $this->output->progressAdvance(500);
            }
        }

        $this->output->progressFinish();
        fclose($file);

        $this->info("Successfully generated {$count} mock questions.");
        return Command::SUCCESS;
    }
}
