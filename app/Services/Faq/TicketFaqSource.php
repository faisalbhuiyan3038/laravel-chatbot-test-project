<?php

namespace App\Services\Faq;

use Generator;
use Illuminate\Support\Facades\DB;

class TicketFaqSource implements FaqSource
{
    public function documents(): Generator
    {
        $rows = DB::table('tickets')
            ->where('status', 'resolved')
            ->whereNotNull('resolution')
            ->orderBy('id') // required for a safe cursor
            ->lazy(500);

        foreach ($rows as $ticket) {
            $question = $this->cleanText($ticket->subject . "\n" . $ticket->description);
            $answer   = $this->cleanText($ticket->resolution);

            // Skip junk: empty fields or placeholder non-answers
            if (mb_strlen($question) < 5 || mb_strlen($answer) < 20) {
                continue;
            }

            yield [
                'id'         => $ticket->id,
                'question'   => $question,
                'answer'     => $answer,
                'updated_at' => $ticket->updated_at,
            ];
        }
    }

    private function cleanText(string $text): string
    {
        $text = strip_tags($text);           // remove HTML from rich-text fields
        $text = html_entity_decode($text);    // &nbsp; etc → real characters
        $text = preg_replace('/\s+/', ' ', $text); // collapse whitespace/newlines
        return trim($text);
    }
}