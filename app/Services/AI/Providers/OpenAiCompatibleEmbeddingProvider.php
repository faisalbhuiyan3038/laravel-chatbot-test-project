<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\EmbeddingProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiCompatibleEmbeddingProvider implements EmbeddingProvider
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly string $model,
    ){}

    public function embed(string $text): array
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(60)
            ->post(rtrim($this->baseUrl, '/').'/embeddings', [
                'model' => $this->model,
                'input' => $text,
            ]);
        
        if($response->failed()){
            throw new RuntimeException("Embedding request failed ({$this->baseUrl}): " . $response->body());
        }

        return $response->json('data.0.embedding');
    }
}