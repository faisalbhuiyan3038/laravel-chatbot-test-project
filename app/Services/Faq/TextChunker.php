<?php

namespace App\Services\Faq;

class TextChunker
{
    public function __construct(
        private readonly int $maxChars = 2000,    
        private readonly int $overlapChars = 200,
    ) {}

    /**
     * @return string[]
     */
    public function chunk(string $question, string $answer): array
    {
        // Most tickets are short enough to stay as one chunk — the common case.
        if (mb_strlen($answer) <= $this->maxChars) {
            return ["Q: {$question}\nA: {$answer}"];
        }

        // Long ticket: split with overlap, but re-prepend the question to
        // EVERY piece. Without this, a fragment from the middle of a long
        // answer has no idea what question it's answering, and semantic
        // search on that fragment alone matches poorly.
        $chunks = [];
        $start = 0;
        $length = mb_strlen($answer);

        while ($start < $length) {
            $piece = mb_substr($answer, $start, $this->maxChars);
            $chunks[] = "Q: {$question}\nA (part): {$piece}";
            $start += $this->maxChars - $this->overlapChars;
        }

        return $chunks;
    }
}