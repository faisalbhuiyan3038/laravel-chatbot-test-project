<?php

namespace App\Services\Faq;

use App\Services\AI\Contracts\EmbeddingProvider;
use App\Services\Faq\LanguageDetector;
use App\Services\Faq\BanglishNormalizer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Services\Faq\VectorCodec;

class FaqRetriever
{
    private readonly BanglishNormalizer $banglishNormalizer;

    public function __construct(
        private readonly EmbeddingProvider $embedder,
        private readonly LanguageDetector $languageDetector,
        private readonly float $fallbackThreshold = 0.5,
        ?BanglishNormalizer $banglishNormalizer = null,
    ) {
        $this->banglishNormalizer = $banglishNormalizer ?? app(BanglishNormalizer::class);
    }

    /**
     * Search FAQ database using multi-lingual vector similarity.
     * Supports English, Bangla, and Banglish (phonetic transliteration).
     *
     * @param string $question
     * @param int $topK
     * @return array<int, array{id:int, question:string, answer:string, score:float}>
     */
    public function search(string $question, int $topK = 4): array
    {
        $currentModel = config('ai.providers.' . config('ai.embedding_provider') . '.embedding_model');
        $lang = $this->languageDetector->detect($question);

        // --- Banglish Handling: Dual Multi-Vector Search ---
        if ($lang === 'banglish') {
            $transliterated = $this->banglishNormalizer->transliterate($question);

            // 1. Vector search transliterated Bangla text against Bangla FAQs
            $bnVector = $this->embedder->embed($transliterated);
            $bnNorm   = VectorCodec::norm($bnVector);
            $bnMatches = $this->scoreAgainst('bn', $currentModel, $bnVector, $bnNorm);

            // 2. Vector search original Latin query against English FAQs
            $enVector = $this->embedder->embed($question);
            $enNorm   = VectorCodec::norm($enVector);
            $enMatches = $this->scoreAgainst('en', $currentModel, $enVector, $enNorm);

            // Merge & deduplicate keeping highest score per FAQ ID
            $merged = [];
            foreach (array_merge($bnMatches, $enMatches) as $match) {
                $id = $match['id'];
                if (!isset($merged[$id]) || $match['score'] > $merged[$id]['score']) {
                    $merged[$id] = $match;
                }
            }

            return $this->rank(array_values($merged), $topK);
        }

        // --- Standard Bangla or English Search ---
        $queryVector = $this->embedder->embed($question);
        $queryNorm   = VectorCodec::norm($queryVector);

        $primary = $this->rank($this->scoreAgainst($lang, $currentModel, $queryVector, $queryNorm), $topK);

        // Fallback cross-lingual check for weak Bangla matches
        $primaryIsWeak = empty($primary) || $primary[0]['score'] < $this->fallbackThreshold;

        if ($lang === 'bn' && $primaryIsWeak) {
            $fallback = $this->rank($this->scoreAgainst('en', $currentModel, $queryVector, $queryNorm), $topK);

            if (!empty($fallback) && (empty($primary) || $fallback[0]['score'] > $primary[0]['score'])) {
                return $fallback;
            }
        }

        return $primary;
    }

    private function scoreAgainst(string $lang, string $currentModel, array $queryVector, float $queryNorm): array
    {
        $scored = [];

        foreach ($this->loadEmbeddedRows($lang, $currentModel) as $row) {
            $scored[] = [
                'id'       => $row['id'],
                'question' => $row['question'],
                'answer'   => $row['answer'],
                'score'    => $this->cosineSimilarity($queryVector, $queryNorm, $row['vector'], $row['norm']),
            ];
        }

        return $scored;
    }

    private function rank(array $scored, int $topK): array
    {
        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($scored, 0, $topK);
    }

    private function cosineSimilarity(array $a, float $normA, array $b, float $normB): float
    {
        if ($normA == 0.0 || $normB == 0.0) {
            return 0.0;
        }

        $dot = 0.0;
        foreach ($a as $i => $val) {
            $dot += $val * $b[$i];
        }

        return $dot / ($normA * $normB);
    }

    private function loadEmbeddedRows(string $lang, string $currentModel): array
    {
        $cols = $lang === 'bn'
            ? ['q' => 'question_bn', 'a' => 'answer_bn', 'emb' => 'embedding_bn', 'model' => 'embedding_bn_model', 'norm' => 'embedding_bn_norm']
            : ['q' => 'question',    'a' => 'answer',    'emb' => 'embedding',    'model' => 'embedding_model',    'norm' => 'embedding_norm'];

        return Cache::remember(
            "faq_embeddings:{$lang}:{$currentModel}",
            now()->addMinutes(10),
            fn () => DB::table('faqs')
                ->where($cols['model'], $currentModel)
                ->whereNotNull($cols['emb'])
                ->select(
                    'id',
                    "{$cols['q']} as question",
                    "{$cols['a']} as answer",
                    "{$cols['emb']} as embedding",
                    "{$cols['norm']} as norm",
                )
                ->get()
                ->map(fn ($row) => [
                    'id'       => $row->id,
                    'question' => $row->question,
                    'answer'   => $row->answer,
                    'vector'   => VectorCodec::decode($row->embedding),
                    'norm'     => $row->norm,
                ])
                ->all()
        );
    }
}