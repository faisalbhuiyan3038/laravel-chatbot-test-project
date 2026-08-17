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

  issue_create — The user is EXPLICITLY AND UNAMBIGUOUSLY asking the assistant to create/open/submit/log/file a NEW support ticket RIGHT NOW in this conversation. Examples: "I want to create an issue", "Please open a ticket for me", "Submit a ticket", "Create an issue", "Log a ticket".
  issue_query  — The user is asking the assistant to look up, list, check status, or show details of their existing issues/tickets. Examples: "Show my tickets", "What's the status of issue #3?", "List my open tickets".
  issue_update — The user is explicitly asking to modify or change an existing ticket. Examples: "Change the category of issue #5", "Update my ticket details", "Edit issue #10".
  issue_delete — The user is explicitly asking to cancel, remove, or delete an existing ticket. Examples: "Delete issue #12", "Remove my ticket", "Cancel ticket #3".
  faq          — EVERYTHING ELSE. This includes:
                   • Problem/complaint descriptions ("I can't login", "my password is wrong", "how do I get my admit card", "লগিন করতে পারছি না")
                   • How-to and procedure questions ("How do I log a ticket?", "What are the steps?")
                   • General knowledge questions about the system
                   • Small talk and greetings
                   • Any ambiguous message that is not a clear, explicit ticket action request

CRITICAL RULES:
1. PROBLEM DESCRIPTIONS ARE NOT TICKET REQUESTS: A user describing a problem ("I can't login", "payment not working", "password wrong") is NOT requesting ticket creation. They want help/information. Classify as `faq`.
2. EXPLICIT ACTION ONLY: Classify as `issue_create`/`issue_query`/`issue_update`/`issue_delete` ONLY when the user explicitly uses action verbs like "create", "open", "submit", "log", "show me", "delete", "update" in relation to a ticket/issue.
3. SAFE DEFAULT: When in doubt, classify as `faq`. It is far better to answer a question than to wrongly start a ticket creation flow.
4. Output ONLY the exact category token (`issue_create`, `issue_query`, `issue_update`, `issue_delete`, or `faq`). Nothing else. No punctuation, no quotes, no explanation.
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
            $intent = strtolower($raw);

            \Illuminate\Support\Facades\Log::info('[IntentDetector] Classification result', [
                'question'   => $question,
                'raw_llm'    => $raw,
                'intent'     => in_array($intent, self::VALID_INTENTS, true) ? $intent : 'faq (unrecognized token)',
            ]);

            if (in_array($intent, self::VALID_INTENTS, true)) {
                return $intent;
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[IntentDetector] Failed, defaulting to faq', [
                'error' => $e->getMessage(),
            ]);
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
