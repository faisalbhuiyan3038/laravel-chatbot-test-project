<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\ChatProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiChatProvider implements ChatProvider
{
    public function complete(string $systemPrompt, string $userPrompt): string
    {
        $model = config('ai.gemini.chat_model');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        $response = Http::timeout(60)->post($url.'?key='.config('ai.gemini.api_key'), [
            'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
            'contents' => [
                ['role' => 'user', 'parts' => [['text', $userPrompt]]],
            ],
        ]);

        if($response->failed()){
            throw new RuntimeException('Gemini chat request failed: '. $response->body());
        }

        return $response->json('candidates.0.content.0.text');
    }
}