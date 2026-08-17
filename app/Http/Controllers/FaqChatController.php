<?php

namespace App\Http\Controllers;

use App\Services\Faq\RagAnswerer;
use App\Services\Issue\IssueAiService;
use App\Services\Issue\IssueIntentDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
        if (is_string($request->input('history'))) {
            $request->merge(['history' => json_decode($request->input('history'), true)]);
        }

        $validated = $request->validate([
            'question'          => ['required', 'string', 'max:1000'],
            'history'           => ['nullable', 'array'],
            'history.*.role'    => ['required', 'string', 'in:user,assistant'],
            'history.*.content' => ['required', 'string', 'max:4000'],
            'attachments'       => ['nullable', 'array', 'max:3'],
            'attachments.*'     => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:2048'],
            'conversation_id'   => ['nullable', 'string', 'max:128'],
        ]);

        $question = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $validated['question']));

        if ($question === '') {
            abort(422, 'Please enter a question.');
        }

        $history = $validated['history'] ?? [];

        $convId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $validated['conversation_id'] ?? '');
        $sessionKey = $convId !== '' ? "ai_issue_session_{$convId}" : 'ai_issue_session';

        $user = Auth::user();
        $issueActionsEnabled = config('ai.issue_actions.enabled', true);

        $activeIssueSession = session($sessionKey, []);
        $activePhase = $activeIssueSession['phase'] ?? null;

        $isCancellingFlow = false;
        if ($activePhase !== null) {
            $qt = mb_strtolower(trim($question));
            $isCancellingFlow = (bool) preg_match(
                '/\b(no|don\'t|dont|not?\s+create|not?\s+open|not?\s+submit|না|লাগবে\s*না|করতে\s*চাই\s*না|দরকার\s*নেই)\b/u',
                $qt
            ) && !preg_match('/\b(category|details|date|change|edit|update|modify|attach|file)\b/u', $qt);
        }

        if ($isCancellingFlow) {
            session()->forget($sessionKey);
            session()->save();
            $activePhase = null;
        }

        $intent = 'faq';
        if ($issueActionsEnabled && $activePhase !== null) {
            $intent = $activeIssueSession['intent'] ?? 'issue_create';
        } elseif ($issueActionsEnabled) {
            $intent = $intentDetector->detect($question, $history);
        }

        Log::info('[ChatFlow] Request received', [
            'conversation_id' => $convId,
            'session_key'     => $sessionKey,
            'user_id'         => $user?->id,
            'question'        => $question,
            'history_count'   => count($history),
            'active_phase'    => $activePhase,
            'is_cancelling'   => $isCancellingFlow,
            'final_intent'    => $intent,
        ]);

        // ── Process Temporary Attachments ─────────────────────────────────────
        $uploadedAttachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    // On some Windows/Laragon setups, $file->getRealPath() returns false due to temp folder permissions,
                    // causing Laravel's store() to throw a ValueError in fopen().
                    // We bypass this by reading the raw temp path directly.
                    $hashName = $file->hashName();
                    $tempPath = 'temp_attachments/' . $hashName;
                    
                    \Illuminate\Support\Facades\Storage::disk('local')->put(
                        $tempPath,
                        file_get_contents($file->getPathname())
                    );
                    
                    $uploadedAttachments[] = [
                        'path'          => $tempPath,
                        'original_name' => $file->getClientOriginalName(),
                        'mime'          => $file->getClientMimeType(),
                        'size'          => $file->getSize(),
                    ];
                }
            }
        }

        return response()->stream(
            function () use ($question, $history, $answerer, $issueService, $intent, $user, $uploadedAttachments, $sessionKey) {
                $requestStart = microtime(true);

                // ── Route to issue service or existing RAG answerer ───────────
                if (in_array($intent, ['issue_create', 'issue_query', 'issue_update', 'issue_delete'], true)) {
                    $result = $issueService->handleStream(
                        intent:      $intent,
                        question:    $question,
                        onToken:     function (string $token) {
                            echo 'data: ' . json_encode(['token' => $token]) . "\n\n";
                            if (ob_get_level() > 0) { ob_flush(); }
                            flush();
                        },
                        history:     $history,
                        user:        $user,
                        attachments: $uploadedAttachments,
                        sessionKey:  $sessionKey,
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

                Log::info('[ChatFlow] Response completed', [
                    'intent'        => $intent,
                    'grounded'      => $result['grounded'],
                    'sources_count' => count($result['sources']),
                    'retrieval_ms'  => $result['retrieval_ms'],
                    'generation_ms' => $result['generation_ms'],
                    'total_ms'      => $totalMs,
                ]);

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