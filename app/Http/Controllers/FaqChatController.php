<?php

namespace App\Http\Controllers;

use App\Services\Faq\RagAnswerer;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FaqChatController extends Controller
{
    public function index(): View
    {
        return view('chat');
    }

    public function ask(Request $request, RagAnswerer $answerer): StreamedResponse
    {
        $validated = $request->validate([
            'question'         => ['required', 'string', 'max:1000'],
            'history'          => ['nullable', 'array'],
            'history.*.role'   => ['required', 'string', 'in:user,assistant'],
            'history.*.content' => ['required', 'string', 'max:4000'],
        ]);

        $question = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $validated['question']));

        if ($question === '') {
            // catches whitespace-only / control-character-only submissions that
            // pass Laravel's basic 'required|string' check but are still junk
            abort(422, 'Please enter a question.');
        }
        
        $history = $validated['history'] ?? [];

        return response()->stream(function () use ($question, $history, $answerer) {
            $requestStart = microtime(true);

            $result = $answerer->answerStream($question, function (string $token) {
                echo 'data: ' . json_encode(['token' => $token]) . "\n\n";
                if (ob_get_level() > 0) { ob_flush(); }
                flush();
            }, $history);

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
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}