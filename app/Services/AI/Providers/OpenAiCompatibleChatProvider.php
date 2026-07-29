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

    public function completeStream(string $systemPrompt, string $userPrompt, callable $onToken): void
    {
        $response = Http::withToken($this->apiKey)
            ->withOptions(['stream' => true]) // tells Guzzle not to buffer the whole body
            ->timeout(120)
            ->post(rtrim($this->baseUrl, '/') . '/chat/completions', [
                'model' => $this->model,
                'stream' => true,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
            ]);
    
        if ($response->failed()) {
            throw new RuntimeException("Chat stream request failed ({$this->baseUrl}): " . $response->body());
        }
    
        $body = $response->toPsrResponse()->getBody();
        $buffer = '';
    
        while (! $body->eof()) {
            $buffer .= $body->read(1024);
    
            // SSE messages are separated by a blank line
            while (($pos = strpos($buffer, "\n\n")) !== false) {
                $frame = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 2);
    
                foreach (explode("\n", $frame) as $line) {
                    $line = trim($line);
                    if (! str_starts_with($line, 'data:')) {
                        continue;
                    }
    
                    $data = trim(substr($line, 5));
                    if ($data === '[DONE]') {
                        return;
                    }
    
                    $token = json_decode($data, true)['choices'][0]['delta']['content'] ?? null;
                    if ($token !== null && $token !== '') {
                        $onToken($token);
                    }
                }
            }
        }
    }
}