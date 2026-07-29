<?php

namespace App\Services\Faq;

use App\Services\AI\Contracts\ChatProvider;

class RagAnswerer
{
    private const NO_ANSWER = "I don't have information about that in the AV-CRM knowledge base yet. Could you rephrase, or contact support directly on this one?";

    public function __construct(
        private readonly FaqRetriever $retriever,
        private readonly ChatProvider $chat,
        private readonly float $similarityThreshold = 0.5,
    ) {}

    public function answer(string $question): array
    {
        $matches = $this->retriever->search($question, topK: 4);

        if(empty($matches) || $matches[0]['score'] < $this->similarityThreshold){
            return [
                'answer' => self::NO_ANSWER,
                'grounded' => false,
                'sources' => [],
                'top_score' => $matches[0]['score'] ?? null,
            ];
        }

        $relevant = array_filter($matches, fn($m) => $m['score'] >= $this->similarityThreshold);
        
        $answer = $this->chat->complete(
            $this->buildSystemPrompt(),
            $this->buildUserPrompt($question, $relevant)
        );

        return [
            'answer' => $answer,
            'grounded' => true,
            'sources' => array_map(fn($m) => ['id' => $m['id'], 'score' => $m['score']], $relevant),
            'top_score' => $matches[0]['score'],
        ];
    }

    
    public function answerStream(string $question, callable $onToken): array
    {
        $retrievalStart = microtime(true);
        $matches = $this->retriever->search($question, topK: 4);
        $retrievalMs = round((microtime(true) - $retrievalStart) * 1000, 1);
    
        if (empty($matches) || $matches[0]['score'] < $this->similarityThreshold) {
            $onToken(self::NO_ANSWER);
            return [
                'grounded'      => false,
                'sources'       => [],
                'top_score'     => $matches[0]['score'] ?? null,
                'retrieval_ms'  => $retrievalMs,
                'generation_ms' => 0,
            ];
        }
    
        $relevant = array_filter($matches, fn ($m) => $m['score'] >= $this->similarityThreshold);
    
        $generationStart = microtime(true);
        $this->chat->completeStream(
            $this->buildSystemPrompt(),
            $this->buildUserPrompt($question, $relevant),
            $onToken
        );
        $generationMs = round((microtime(true) - $generationStart) * 1000, 1);
    
        return [
            'grounded' => true,
            'sources'  => array_map(fn ($m) => [
                'id'       => $m['id'],
                'question' => $m['question'],
                'score'    => round($m['score'], 4),
            ], $relevant),
            'top_score'     => $matches[0]['score'],
            'retrieval_ms'  => $retrievalMs,
            'generation_ms' => $generationMs,
        ];
    }

    private function buildSystemPrompt(): string
    {
        return <<<PROMPT
            You are a support assistant inside AV-CRM, a CRM used to record and close support tickets. 
            Answer ONLY using the context provided below - do not use any outside knowledge, and do not guess at features or workflows that aren't described in the context.

            If the context does not contain enough information to answer the question, say so plainly instead of guessing. Never invent menu names, button labels, or steps that aren't in the context.

            Keep answers concise and practical - 2-4 sentences unless the question genuinely needs a numbered list of steps.
            PROMPT;
    }

    private function buildUserPrompt(string $question, array $matches): string
    {
        $context = collect($matches)
            ->map(fn($m, $i) => "[{$i}] Q: {$m['question']}\nA: {$m['answer']}")
            ->implode("\n\n");

        return <<<PROMPT
            Context:
            {$context}

            User question: {$question}
            PROMPT;
    }
}