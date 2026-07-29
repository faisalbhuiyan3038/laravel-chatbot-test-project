<?php

namespace App\Services\Faq;

use App\Services\AI\Contracts\EmbeddingProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Services\Faq\VectorCodec;

class FaqRetriever
{
    public function __construct(private readonly EmbeddingProvider $embedder){}

    /**
     * Summary of search
     * @param string $question
     * @param int $topK
     * @return array<int, array{id:int, question:string, answer:string, score:float}>
     */
    public function search(string $question, int $topK = 4): array
    {
        $currentModel = config('ai.providers.'.config('ai.embedding_provider').'.embedding_model');

        $queryVector = $this->embedder->embed($question);
        $queryNorm = VectorCodec::norm($queryVector);

        $scored = [];

        foreach($this->loadEmbeddedRows($currentModel) as $row){
            $scored[] = [
                'id' => $row['id'],
                'question' => $row['question'],
                'answer' => $row['answer'],
                'score' => $this->cosineSimilarity($queryVector, $queryNorm, $row['vector'], $row['norm']),
            ];
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, $topK);
    }

    private function cosineSimilarity(array $a, float $normA, array $b, float $normB): float
    {
        if($normA == 0.0 || $normB == 0.0){
            return 0.0;
        }

        $dot = 0.0;
        foreach($a as $i => $val){
            $dot += $val * $b[$i];
        }

        return $dot / ($normA * $normB);
    }

    /**
     * Cached so a 50k-row scan doesn't hit the DB and re-decode every
     * binary vector on every single question — only when the cache
     * expires or EmbedFaqs explicitly clears it (see Phase 3 update below).
     */
    private function loadEmbeddedRows(string $currentModel): array
    {
        return Cache::remember(
            "faq_embeddings:{$currentModel}",
            now()->addMinutes(10),
            function() use ($currentModel){
                return DB::table('faqs')
                    ->where('embedding_model', $currentModel)
                    ->whereNotNull('embedding')
                    ->select('id', 'question', 'answer', 'embedding', 'embedding_norm')
                    ->get()
                    ->map(fn ($row) => [
                        'id'       => $row->id,
                        'question' => $row->question,
                        'answer'   => $row->answer,
                        'vector'   => VectorCodec::decode($row->embedding),
                        'norm'     => $row->embedding_norm,
                    ])
                    ->all();
            }
        );
    }
}