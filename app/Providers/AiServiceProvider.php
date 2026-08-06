<?php

namespace App\Providers;

use App\Services\AI\Contracts\ChatProvider;
use App\Services\AI\Contracts\EmbeddingProvider;
use App\Services\AI\Providers\OpenAiCompatibleChatProvider;
use App\Services\AI\Providers\OpenAiCompatibleEmbeddingProvider;
use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EmbeddingProvider::class, function(){
            $cfg = config('ai.providers.' . config('ai.embedding_provider'));

            return new OpenAiCompatibleEmbeddingProvider(
                baseUrl: $cfg['base_url'],
                apiKey:  $cfg['api_key'],
                model:   $cfg['embedding_model'],
            );
        });

        $this->app->bind(ChatProvider::class, function(){
            $cfg = config('ai.providers.'. config('ai.chat_provider'));

            return new OpenAiCompatibleChatProvider(
                baseUrl: $cfg['base_url'],
                apiKey: $cfg['api_key'],
                model: $cfg['chat_model'],
            );
        });

        $this->app->bind('ai.translator', function(){
            $cfg = config('ai.providers.'. config('ai.translation_provider'));

            return new OpenAiCompatibleChatProvider(
                baseUrl: $cfg['base_url'],
                apiKey: $cfg['api_key'],
                model: $cfg['translation_model'],
            );
        });
    }
}