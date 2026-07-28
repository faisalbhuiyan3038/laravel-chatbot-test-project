<?php

namespace App\Providers;

use App\Services\AI\Contracts\ChatProvider;
use App\Services\AI\Contracts\EmbeddingProvider;
use App\Services\AI\Providers\GeminiChatProvider;
use App\Services\AI\Providers\GeminiEmbeddingProvider;
use App\Services\AI\Providers\OllamaChatProvider;
use App\Services\AI\Providers\OllamaEmbeddingProvider;
use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EmbeddingProvider::class, fn () => match (config('ai.embedding.driver')){
            'gemini' => new GeminiEmbeddingProvider(),
            default => new OllamaEmbeddingProvider(),
        });

        $this->app->bind(ChatProvider::class, fn () => match (config('ai.chat.driver')) {
            'gemini' => new GeminiChatProvider(),
            default => new OllamaChatProvider(),
        });
    }
}