<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\EmbeddingProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiEmbeddingProvider implements EmbeddingProvider
{
    public function embed(string $text): array
    {
        $model = config('ai.gemini.embedding_model');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:embedContent";

        $response = Http::timeout(60)->post($url . '?key=' . config('ai.gemini.api_key', [
            'content' => ['parts' => [['text' => $text]]],
        ]));

        if ($response->failed()){
            throw new RuntimeException('Gemini embedding request failed: '. $response->body());
        }

        return $response->json('embedding.values');
    }
}