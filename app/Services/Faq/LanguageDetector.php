<?php

namespace App\Services\Faq;

class LanguageDetector
{
    /**
     * Distinguishes Bangla-script text from everything else, using the
     * Unicode Bengali block (U+0980–U+09FF). This is a script check, not
     * a language check — it can't tell English apart from Banglish
     * (Bangla typed in Latin letters), since both look identical to a
     * Unicode range test. That distinction is a separate problem for later.
     */
    public function detect(string $text): string
    {
        $totalLetters = preg_match_all('/\p{L}/u', $text);

        if ($totalLetters === 0) {
            return 'en'; // no letters at all (e.g. just punctuation/numbers) — safe default
        }

        $banglaLetters = preg_match_all('/[\x{0980}-\x{09FF}]/u', $text);

        // Assumption flag: 30% is a deliberately low bar, so a mostly-English
        // sentence with a couple of Bangla words still routes to Bangla search.
        // Tune this if code-switched messages start landing on the wrong side.
        return ($banglaLetters / $totalLetters) > 0.3 ? 'bn' : 'en';
    }
}