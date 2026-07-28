<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\ChatProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiCompatibleChatProvider implements ChatProvider
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly string $model,
    ) {}

    public function complete(string $systemPrompt, string $userPrompt): string
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(120)
            ->post(rtrim($this->baseUrl, '/') . '/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException("Chat request failed ({$this->baseUrl}): " . $response->body());
        }

        return $response->json('choices.0.message.content');
    }
}