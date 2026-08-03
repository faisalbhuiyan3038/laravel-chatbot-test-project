<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Services\AI\Contracts\EmbeddingProvider;
use App\Services\Faq\VectorCodec;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

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

    private const LANGUAGES = [
        'en' => [
            'question_col'  => 'question',
            'answer_col'    => 'answer',
            'embedding_col' => 'embedding',
            'model_col'     => 'embedding_model',
            'norm_col'      => 'embedding_norm',
            'stamp_col'     => 'embedded_at',
        ],
        'bn' => [
            'question_col'  => 'question_bn',
            'answer_col'    => 'answer_bn',
            'embedding_col' => 'embedding_bn',
            'model_col'     => 'embedding_bn_model',
            'norm_col'      => 'embedding_bn_norm',
            'stamp_col'     => 'embedded_bn_at',
        ],
    ];

    public function handle(EmbeddingProvider $embedder): int
    {
        $currentModel = config('ai.providers.' . config('ai.embedding_provider') . '.embedding_model');

        foreach (self::LANGUAGES as $lang => $cols) {
            $this->embedLanguage($lang, $cols, $embedder, $currentModel);
        }

        Cache::forget("faq_embeddings:en:{$currentModel}");
        Cache::forget("faq_embeddings:bn:{$currentModel}");

        return self::SUCCESS;
    }

    private function embedLanguage(string $lang, array $cols, EmbeddingProvider $embedder, string $currentModel): void
    {
        $baseQuery = fn () => DB::table('faqs')
            ->whereNotNull($cols['question_col'])
            ->whereNotNull($cols['answer_col'])
            ->when(! $this->option('force'), fn ($q) => $q->where(function ($q) use ($cols, $currentModel) {
                $q->whereNull($cols['embedding_col'])->orWhere($cols['model_col'], '!=', $currentModel);
            }));

        $total = $baseQuery()->count();

        if ($total === 0) {
            $this->info("[{$lang}] Nothing to embed.");
            return;
        }

        $this->info("[{$lang}] Embedding {$total} row(s) using model: {$currentModel}");
        $bar = $this->output->createProgressBar($total);

        foreach ($baseQuery()->orderBy('id')->lazy(200) as $row) {
            try {
                $text = "Q: {$row->{$cols['question_col']}}\nA: {$row->{$cols['answer_col']}}";
                $vector = $embedder->embed($text);

                DB::table('faqs')->where('id', $row->id)->update([
                    $cols['embedding_col'] => VectorCodec::encode($vector),
                    $cols['model_col']     => $currentModel,
                    $cols['norm_col']      => VectorCodec::norm($vector),
                    $cols['stamp_col']     => now(),
                ]);
            } catch (\Throwable $e) {
                $this->newLine();
                $this->warn("[{$lang}] Failed on FAQ #{$row->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }
}
