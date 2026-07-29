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
            'question' => ['required', 'string', 'max:1000'],
        ]);

        return response()->stream(function () use ($validated, $answerer) {
            $requestStart = microtime(true);

            $result = $answerer->answerStream($validated['question'], function (string $token) {
                echo 'data: ' . json_encode(['token' => $token]) . "\n\n";
                if (ob_get_level() > 0) { ob_flush(); }
                flush();
            });

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