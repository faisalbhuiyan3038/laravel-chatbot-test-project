<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\EmbeddingProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OllamaEmbeddingProvider implements EmbeddingProvider
{
    public function embed(string $text): array
    {
        $response = Http::timeout(60)->post(
            config('ai.ollama.base_url') . '/api/embeddings',
            [
                'model'  => config('ai.ollama.embedding_model'),
                'prompt' => $text,
            ]
        );

        if ($response->failed()) {
            throw new RuntimeException('Ollama embedding request failed: ' . $response->body());
        }

        return $response->json('embedding');
    }
}