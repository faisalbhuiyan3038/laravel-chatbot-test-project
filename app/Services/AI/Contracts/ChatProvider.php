<?php

namespace App\Services\AI\Contracts;

interface ChatProvider
{
    public function complete(string $systemPrompt, string $userPrompt): string;

    /**
     * Same as complete(), but calls $onToken(string $token) as each
     * piece of the reply arrives, instead of returning the full string.
     */
    public function completeStream(string $systemPrompt, string $userPrompt, callable $onToken): void;

    /**
     * Stream responses for an array of structured messages (system, user, assistant history).
     *
     * @param array<int, array{role: string, content: string}> $messages
     * @param callable(string): void $onToken
     */
    public function completeMessagesStream(array $messages, callable $onToken): void;

    /**
     * Complete based on an array of messages, returning the full string.
     *
     * @param array<int, array{role: string, content: string}> $messages
     */
    public function completeMessages(array $messages): string;
}