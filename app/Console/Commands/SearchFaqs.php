<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Faq\FaqRetriever;

class SearchFaqs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'faq:search {question} {--top=4}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test semantic search against stored FAQ embeddings';

    /**
     * Execute the console command.
     */
    public function handle(FaqRetriever $retriever): int
    {
        $results = $retriever->search($this->argument('question'), (int) $this->option('top'));

        if(empty($results)){
            $this->warn('No results - has faq:embed been run yet?');
            return self::SUCCESS;
        }

        foreach($results as $i => $r){
            $this->info(sprintf('#%d  [score: %.4f]  FAQ #%d', $i + 1, $r['score'], $r['id']));
            $this->line('Q: '.$r['question']);
            $this->line('A: '.mb_substr($r['answer'], 0, 150).'...');
            $this->newLine();
        }

        return self::SUCCESS;
    }
}
