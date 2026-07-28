<?php

namespace App\Services\Faq;

use Generator;
use Illuminate\Support\Facades\DB;
use App\Services\Faq\FaqSource;


class DbFaqSource implements FaqSource
{
    public function documents(): Generator
    {
        $rows = DB::table('faqs')->orderBy('id')->lazy(500);

        foreach($rows as $faq){
            $question = $this->cleanText($faq->question);
            $answer   = $this->cleanText($faq->answer);

            if (mb_strlen($question) < 5 || mb_strlen($answer) < 5) {
                continue;
            }

            yield [
                'id'         => $faq->id,
                'question'   => $question,
                'answer'     => $answer,
                'updated_at' => $faq->updated_at,
            ];
        }
    }

    private function cleanText(string $text): string
    {
        $text = strip_tags($text);
        $text = html_entity_decode($text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
}