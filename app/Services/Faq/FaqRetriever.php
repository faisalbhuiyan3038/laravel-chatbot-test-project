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
     * @return array<int, array{id:int, project_id:int, question:string, answer:string, score:float}>
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
                'id'         => $row['id'],
                'project_id' => $row['project_id'],
                'question'   => $row['question'],
                'answer'     => $row['answer'],
                'score'      => $this->cosineSimilarity($queryVector, $queryNorm, $row['vector'], $row['norm']),
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
        return Cache::remember(
            "knowledge_embeddings:{$lang}:{$currentModel}",
            now()->addMinutes(10),
            fn () => DB::table('knowledge_chunks')
                ->where('language', $lang)
                ->where('embedding_model', $currentModel)
                ->whereNotNull('embedding')
                ->select(
                    'id',
                    'project_id',
                    'title as question',
                    'content as answer',
                    'embedding',
                    'embedding_norm as norm',
                )
                ->get()
                ->map(fn ($row) => [
                    'id'         => $row->id,
                    'project_id' => $row->project_id,
                    'question'   => $row->question,
                    'answer'     => $row->answer,
                    'vector'     => VectorCodec::decode($row->embedding),
                    'norm'       => $row->norm,
                ])
                ->all()
        );
    }
}