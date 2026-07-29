<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Faq\RagAnswerer;

class AskFaq extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'faq:ask {question}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ask a question through the full RAG pipeline';

    /**
     * Execute the console command.
     */
    public function handle(RagAnswerer $answerer)
    {
        $result = $this->answerer($answerer);

        $this->info('Grounded: '.($result['grounded'] ? 'yes' : 'no'));
        $this->info('Top score: '.round($result['top_score'] ?? 0, 4));

        if($result['sources']){
            $this->line('Sources: ' . collect($result['sources'])
                ->map(fn ($s) => "#{$s['id']} ({$s['score']})")
                ->implode(', '));
        }

        $this->newLine();
        $this->line('Answer:');
        $this->line($result['answer']);

        return self::SUCCESS;
    }

    private function answerer(RagAnswerer $answerer): array
    {
        return $answerer->answer($this->argument('question'));
    }
}
