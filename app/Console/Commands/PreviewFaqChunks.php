<?php

namespace App\Console\Commands;

use App\Services\Faq\TextChunker;
use App\Services\Faq\DbFaqSource;
use Illuminate\Console\Command;

class PreviewFaqChunks extends Command
{
    protected $signature = 'faq:preview {--limit=5}';
    protected $description = 'Preview extracted tickets and how they get chunked';

    public function handle(DbFaqSource $source, TextChunker $chunker): int
    {
        $limit = (int) $this->option('limit');
        $count = 0;

        foreach ($source->documents() as $doc) {
            if ($count >= $limit) {
                break;
            }

            $chunks = $chunker->chunk($doc['question'], $doc['answer']);

            $this->info("--- Ticket #{$doc['id']} → " . count($chunks) . ' chunk(s) ---');
            foreach ($chunks as $i => $chunk) {
                $this->line("[chunk {$i}] " . mb_substr($chunk, 0, 150) . '...');
            }

            $count++;
        }

        $this->info("Previewed {$count} ticket(s).");

        return self::SUCCESS;
    }
}