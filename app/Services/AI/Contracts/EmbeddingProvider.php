<?php

namespace App\Services\AI\Contracts;

interface EmbeddingProvider
{
    /**
     * Turn text into a vector of floats capturing its meaning.
     *
     * @return float[]
     */
    public function embed(string $text): array;
}