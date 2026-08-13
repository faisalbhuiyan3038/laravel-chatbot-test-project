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
    private const VALID_INTENTS = ['issue_create', 'issue_query', 'issue_update', 'issue_delete', 'faq'];

    private const SYSTEM_PROMPT = <<<'PROMPT'
You are an intent classification assistant for a customer support chatbot.

Your task: read the conversation history and the user's latest message and classify the intent into ONE of these five categories:

  issue_create — The user is ACTIVELY REQUESTING the assistant to create, open, submit, or report a new support issue/ticket NOW in this chat (e.g. "I want to create an issue", "Please open a ticket", "Create a ticket for me", "Report an issue with login", "Submit a ticket").
  issue_query  — The user is asking the assistant to look up, list, check status, or show details of THEIR OWN existing issues/tickets (e.g. "Show my tickets", "What is the status of my issue #3?", "List my open tickets", "Do I have any open tickets?").
  issue_update — The user is asking to modify, edit, or change an existing ticket (e.g., "Change the category of issue #5", "Update my ticket details", "Add this attachment to issue #10").
  issue_delete — The user is asking to cancel, remove, or delete an existing ticket (e.g., "Delete issue #12", "Cancel my ticket").
  faq          — Documentation questions, how-to questions, procedure/process questions, policy questions, general inquiries, or small talk (e.g. "How to log a new ticket in AV-CRM?", "How do I create an issue?", "What is the process to submit a ticket?", "Who is allowed to create a ticket?").

CRITICAL RULES:
1. HOW-TO / PROCEDURE QUESTIONS: Questions asking HOW to do something, what the steps or process are, or asking about documentation (e.g., "How to log a new ticket in AV-CRM?", "How do I submit an issue?", "What are the steps to open a ticket?") are ALWAYS `faq`.
2. OPERATIONAL ACTION REQUESTS: Classify as `issue_create`, `issue_query`, `issue_update`, or `issue_delete` ONLY when the user is explicitly asking to start or execute ticket actions right now in this chat session.
3. Output ONLY the exact category token (`issue_create`, `issue_query`, `issue_update`, `issue_delete`, or `faq`). Nothing else. No punctuation, no quotes, no explanation.
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
