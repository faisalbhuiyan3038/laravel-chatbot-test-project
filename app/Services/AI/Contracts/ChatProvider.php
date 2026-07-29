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
}