<?php

namespace App\Http\Controllers;

use App\Services\Faq\RagAnswerer;
use App\Services\Issue\IssueAiService;
use App\Services\Issue\IssueIntentDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FaqChatController extends Controller
{
    public function index(): View
    {
        return view('chat');
    }

    public function ask(
        Request             $request,
        RagAnswerer         $answerer,
        IssueIntentDetector $intentDetector,
        IssueAiService      $issueService,
    ): StreamedResponse {
        $validated = $request->validate([
            'question'          => ['required', 'string', 'max:1000'],
            'history'           => ['nullable', 'array'],
            'history.*.role'    => ['required', 'string', 'in:user,assistant'],
            'history.*.content' => ['required', 'string', 'max:4000'],
        ]);

        $question = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $validated['question']));

        if ($question === '') {
            // catches whitespace-only / control-character-only submissions that
            // pass Laravel's basic 'required|string' check but are still junk
            abort(422, 'Please enter a question.');
        }

        $history = $validated['history'] ?? [];

        // ── Resolve authenticated user at the HTTP layer ──────────────────────
        // This value is passed into services explicitly — services never call
        // Auth::user() themselves, so the auth context is always injected, not
        // assumed, and easy to test.
        $user = Auth::user();

        // ── Issue-actions feature flag ────────────────────────────────────────
        // If AI issue actions are disabled globally, skip intent detection and
        // fall straight through to the existing RAG answerer.
        $issueActionsEnabled = config('ai.issue_actions.enabled', true);

        // Detect intent — only run the classifier when the feature is on AND
        // there is a plausible signal that the user is talking about issues.
        // The classifier itself falls back to 'faq' on any error, so the RAG
        // path is always the safe default.
        $intent = 'faq';
        if ($issueActionsEnabled) {
            $intent = $intentDetector->detect($question, $history);
        }

        return response()->stream(
            function () use ($question, $history, $answerer, $issueService, $intent, $user) {
                $requestStart = microtime(true);

                // ── Route to issue service or existing RAG answerer ───────────
                if (in_array($intent, ['issue_create', 'issue_query'], true)) {
                    $result = $issueService->handleStream(
                        intent:   $intent,
                        question: $question,
                        onToken:  function (string $token) {
                            echo 'data: ' . json_encode(['token' => $token]) . "\n\n";
                            if (ob_get_level() > 0) { ob_flush(); }
                            flush();
                        },
                        history:  $history,
                        user:     $user,   // may be null — IssueAiService handles the null case
                    );
                } else {
                    // ── Existing RAG path — completely unchanged ───────────────
                    $result = $answerer->answerStream($question, function (string $token) {
                        echo 'data: ' . json_encode(['token' => $token]) . "\n\n";
                        if (ob_get_level() > 0) { ob_flush(); }
                        flush();
                    }, $history);
                }

                $totalMs = round((microtime(true) - $requestStart) * 1000, 1);

                echo 'data: ' . json_encode([
                    'done'     => true,
                    'grounded' => $result['grounded'],
                    'sources'  => $result['sources'],
                    'timing'   => [
                        'retrieval_ms'  => $result['retrieval_ms'],
                        'generation_ms' => $result['generation_ms'],
                        'total_ms'      => $totalMs,
                    ],
                ]) . "\n\n";
                if (ob_get_level() > 0) { ob_flush(); }
                flush();
            },
            200,
            [
                'Content-Type'      => 'text/event-stream',
                'Cache-Control'     => 'no-cache',
                'X-Accel-Buffering' => 'no',
            ]
        );
    }
}