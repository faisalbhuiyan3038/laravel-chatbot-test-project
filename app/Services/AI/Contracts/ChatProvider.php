<?php

namespace App\Services\AI\Contracts;

interface ChatProvider
{
    public function complete(string $systemPrompt, string $userPrompt): string;
}