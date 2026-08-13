<?php

namespace App\Services\Issue;

use App\Services\AI\Contracts\ChatProvider;

/**
 * Classifies the user's latest chat message into one of three intents:
 *
 *   issue_create — user wants to open a new support issue/ticket
 *   issue_query  — user wants to read/list/check status of their issues
 *   faq          — everything else (falls through to RAG answerer)
 *
 * The classifier uses a single non-streaming LLM call that returns exactly
 * one token. On any error, it falls back to 'faq' so the existing RAG path
 * is always the safe default and never broken by a classifier failure.
 */
class IssueIntentDetector
{
    /** @var string[] Valid intent tokens the LLM may return */
    private const VALID_INTENTS = ['issue_create', 'issue_query', 'faq'];

    private const SYSTEM_PROMPT = <<<'PROMPT'
You are an intent classification assistant for a customer support chatbot.

Your task: read the conversation history and the user's latest message and classify the intent into exactly one of these three categories:

  issue_create — the user wants to create, open, submit, or report a new support issue or ticket
  issue_query  — the user wants to see, list, check the status of, or get details about their existing issues or tickets
  faq          — anything else (a documentation question, a how-to question, small talk, etc.)

Rules:
- Output ONLY the exact category token (issue_create, issue_query, or faq). Nothing else.
- No punctuation, no quotes, no explanation.
- When in doubt, output faq.
PROMPT;

    public function __construct(
        private readonly ChatProvider $chat,
    ) {}

    /**
     * Classify the user's intent.
     *
     * @param  string                              $question  Latest user message
     * @param  array<int, array{role:string, content:string}> $history   Recent conversation turns
     * @return 'issue_create'|'issue_query'|'faq'
     */
    public function detect(string $question, array $history = []): string
    {
        // Limit history to last 6 turns (same as RagAnswerer::reformulateQuery)
        $recentHistory = $this->trimHistory($history, maxMessages: 6, maxChars: 4000);

        $messages = array_merge(
            [['role' => 'system', 'content' => self::SYSTEM_PROMPT]],
            $recentHistory,
            [['role' => 'user', 'content' => $question]],
        );

        try {
            $raw = trim($this->chat->completeMessages($messages));
            // Normalise to lowercase in case the model adds capitalisation
            $intent = strtolower($raw);

            if (in_array($intent, self::VALID_INTENTS, true)) {
                return $intent;
            }
        } catch (\Throwable) {
            // Fall through to safe default
        }

        return 'faq';
    }

    /**
     * Trim conversation history to the most recent N messages and a char budget,
     * preserving chronological order.
     *
     * @param  array<int, array{role:string, content:string}> $history
     * @return array<int, array{role:string, content:string}>
     */
    private function trimHistory(array $history, int $maxMessages, int $maxChars): array
    {
        $result = [];
        $chars = 0;

        foreach (array_reverse($history) as $msg) {
            if (
                !isset($msg['role'], $msg['content'])
                || !in_array($msg['role'], ['user', 'assistant'], true)
            ) {
                continue;
            }

            $len = mb_strlen($msg['content']);

            if ($chars + $len > $maxChars) {
                break;
            }

            if (count($result) >= $maxMessages) {
                break;
            }

            $chars += $len;
            array_unshift($result, [
                'role'    => $msg['role'],
                'content' => (string) $msg['content'],
            ]);
        }

        return $result;
    }
}
