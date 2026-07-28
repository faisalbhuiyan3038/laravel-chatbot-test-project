<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\ChatProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OllamaChatProvider implements ChatProvider
{
    public function complete(string $systemPrompt, string $userPrompt): string
    {
        $response = Http::timeout(120)->post(
            config('ai.ollama.base_url') . '/api/chat',
            [
                'model'  => config('ai.ollama.chat_model'),
                'stream' => false, // we'll enable streaming in Phase 5
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
            ]
        );

        if ($response->failed()) {
            throw new RuntimeException('Ollama chat request failed: ' . $response->body());
        }

        return $response->json('message.content');
    }
}