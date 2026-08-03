<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/faqs.json');

        if(!file_exists($path)){
            $this->command->error("Seed file not found at {$path}");
            return;
        }

        $faqs = json_decode(file_get_contents($path), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error('Invalid JSON: ' . json_last_error_msg());
            return;
        }

        foreach($faqs as $faq){
            DB::table('faqs')->insert([
                'question'    => trim($faq['question']),
                'answer'      => trim($faq['answer']),
                'question_bn' => isset($faq['question_bn']) ? trim($faq['question_bn']) : null,
                'answer_bn'   => isset($faq['answer_bn']) ? trim($faq['answer_bn']) : null,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        $this->command->info('Seeded ' . count($faqs) . ' FAQ entries.');
    }
}