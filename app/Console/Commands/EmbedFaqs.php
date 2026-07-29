<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Services\AI\Contracts\EmbeddingProvider;
use App\Services\Faq\VectorCodec;
use Illuminate\Support\Facades\DB;

class EmbedFaqs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'faq:embed {--force : Re-embed everything, even rows already up to date}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and store embeddings for FAQ rows that need it';

    /**
     * Execute the console command.
     */
    public function handle(EmbeddingProvider $embedder): int
    {
        $currentModel = config('ai.providers.' . config('ai.embedding_provider') . '.embedding_model');

        $baseQuery = fn() => DB::table('faqs')->when(
            ! $this->option('force'),
            fn ($q) => $q->where(function ($q) use ($currentModel) {
                $q->whereNull('embedding')->orWhere('embedding_model', '!=', $currentModel);
            })
        );

        $total = $baseQuery()->count();

        if($total == 0){
            $this->info('Nothing to embed — all rows are already up to date with the current model.');
            return self::SUCCESS;
        }

        $this->info("Embedding {$total} row(s) using model: {$currentModel}");
        $bar = $this->output->createProgressBar($total);
        $failures = [];

        foreach ($baseQuery()->orderBy('id')->lazy(200) as $row) {
            try {
                $text = "Q: {$row->question}\nA: {$row->answer}";
                $vector = $embedder->embed($text);

                DB::table('faqs')->where('id', $row->id)->update([
                    'embedding'       => VectorCodec::encode($vector),
                    'embedding_model' => $currentModel,
                    'embedding_norm'  => VectorCodec::norm($vector),
                    'embedded_at'     => now(),
                ]);
            } catch (\Throwable $e) {
                $failures[] = $row->id;
                $this->newLine();
                $this->warn("Failed on FAQ #{$row->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if ($failures) {
            $this->warn('Failed IDs: ' . implode(', ', $failures) . ' — just re-run faq:embed, it will retry only these.');
        } else {
            $this->info('Done — all rows embedded successfully.');
        }

        return self::SUCCESS;
    }
}
