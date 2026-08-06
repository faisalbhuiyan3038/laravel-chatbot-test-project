<?php

namespace App\Services\Faq;

use App\Services\AI\Contracts\ChatProvider;
use Illuminate\Support\Facades\Cache;

class BanglishNormalizer
{
    public function __construct(
        private readonly ?ChatProvider $translator = null
    ) {}
    /**
     * Core Banglish pronouns, question words, and verbs that uniquely identify Banglish script queries.
     */
    private const CORE_BANGLISH_MARKERS = [
        'amar', 'amader', 'apnar', 'apnader', 'tader', 'ami', 'amra', 'tumi', 'apni',
        'keno', 'kivabe', 'kevbabe', 'kibabe', 'ki', 'kee', 'kon', 'kobe', 'kotokhon', 'koto', 'kar', 'jonno', 'ekhon', 'kokhon',
        'korbo', 'korin', 'korun', 'korte', 'kore', 'korle', 'korechi', 'korechen', 'hobe', 'hocche', 'hoche',
        'hocchena', 'hochena', 'paile', 'pabo', 'pabona', 'pawa', 'parsi', 'parchina', 'parsona', 'parbo', 'pari',
        'somossa', 'shomossha', 'shomossa', 'somossha', 'choltesena', 'choltasena', 'cholse', 'cholche',
        'ashse', 'ashche', 'asche', 'ashsena', 'aschena', 'dorkar', 'lagbe', 'thik', 'bistarito', 'hoyni', 'bujhte',
        'khulle', 'khulbo', 'dekhte', 'jani', 'janate', 'janbo', 'bolben', 'bolun', 'kollester', 'janaben', 'janina', 'parina'
    ];

    /**
     * Dictionary of Banglish keywords & phonetic rules mapped to Bangla script.
     */
    private const BANGLISH_DICTIONARY = [
        // Questions & Pronouns
        'ami'         => 'আমি',
        'amra'        => 'আমরা',
        'tumi'        => 'তুমি',
        'apni'        => 'আপনি',
        'amar'        => 'আমার',
        'amader'      => 'আমাদের',
        'apnar'       => 'আপনার',
        'apnader'     => 'আপনাদের',
        'tader'       => 'তাদের',
        'oboseshe'    => 'অবশেষে',
        'keno'        => 'কেন',
        'kivabe'      => 'কিভাবে',
        'kevbabe'     => 'কিভাবে',
        'kibabe'      => 'কিভাবে',
        'ki'          => 'কি',
        'kee'         => 'কি',
        'kon'         => 'কোন',
        'kobe'        => 'কবে',
        'kotokhon'    => 'কতক্ষণ',
        'koto'        => 'কত',
        'kar'         => 'কার',
        'kahake'      => 'কাকে',
        'jonno'       => 'জন্য',
        'ekhon'       => 'এখন',
        'kokhon'      => 'কখন',
        'er'          => 'এর',
        'ta'          => 'টা',
        'te'          => 'তে',
        'ke'          => 'কে',
        'theke'       => 'থেকে',
        'k'           => 'কে',

        // Support & Call Center Action Verbs
        'korbo'       => 'করব',
        'korin'       => 'করুন',
        'korun'       => 'করুন',
        'korte'       => 'করতে',
        'kore'        => 'করে',
        'korle'       => 'করলে',
        'korechi'     => 'করেছি',
        'korechen'    => 'করেছেন',
        'hobe'        => 'হবে',
        'hocche'      => 'হচ্ছে',
        'hoche'       => 'হচ্ছে',
        'hocchena'    => 'হচ্ছে না',
        'hochena'     => 'হচ্ছে না',
        'paile'       => 'পেলে',
        'parbo'       => 'পারব',
        'pabo'        => 'পাব',
        'pabona'      => 'পাব না',
        'pawa'        => 'পাওয়া',
        'parsi'       => 'পারছি',
        'parchina'    => 'পারছি না',
        'parsona'     => 'পারছ না',

        // Status & Problems
        'somossa'     => 'সমস্যা',
        'shomossha'   => 'সমস্যা',
        'shomossa'    => 'সমস্যা',
        'somossha'    => 'সমস্যা',
        'problem'     => 'সমস্যা',
        'choltesena'  => 'চলছে না',
        'choltasena'  => 'চলছে না',
        'cholse'      => 'চলছে',
        'cholche'     => 'চলছে',
        'ashse'       => 'আসছে',
        'ashche'      => 'আসছে',
        'asche'       => 'আসছে',
        'ashsena'     => 'আসছে না',
        'aschena'     => 'আসছে না',
        'hoyni'       => 'হয়নি',
        'bujhte'      => 'বুঝতে',
        'pari'        => 'পারি',
        'parina'      => 'পারি না',
        'janina'      => 'জানি না',
        'janaben'     => 'জানাবেন',
        'dorkar'      => 'দরকার',
        'lagbe'       => 'লাগবে',
        'thik'        => 'ঠিক',
        'bistarito'   => 'বিস্তারিত',
        'khulle'      => 'খুললে',
        'khulbo'      => 'খুলব',
        'dekhte'      => 'দেখতে',
        'jani'        => 'জানি',
        'janate'      => 'জানাতে',
        'janbo'       => 'জানব',
        'bolben'      => 'বলবেন',
        'bolun'       => 'বলুন',
        'dhonnobad'   => 'ধন্যবাদ',

        // Business & Billing Domain Terms
        'bill'        => 'বিল',
        'biller'      => 'বিলার',
        'ticket'      => 'টিকিট',
        'tickets'     => 'টিকিট',
        'category'    => 'ক্যাটাগরি',
        'kollester'   => 'কল রেজিস্টার',
        'register'    => 'রেজিস্টার',
        'apply'       => 'অ্যাপ্লাই',
        'job'         => 'জব',
        'project'     => 'প্রজেক্ট',
        'payment'     => 'পেমেন্ট',
        'refund'      => 'রিফান্ড',
        'missing'     => 'মিসিং',
        'solution'    => 'সমাধান',
        'somadhan'    => 'সমাধান',
        'connection'  => 'কানেকশন',
        'slow'        => 'স্লো',
        'slowness'    => 'স্লোনেস',
        'net'         => 'নেট',
        'internet'    => 'ইন্টারনেট',
    ];

    /**
     * Determines if a text appears to be Banglish (Bangla words written in Latin script).
     */
    public function isBanglish(string $text): bool
    {
        // 1. Native Bangla Unicode script check -> Not Banglish
        if (preg_match('/[\x{0980}-\x{09FF}]/u', $text)) {
            return false;
        }

        $letters = preg_match_all('/\p{L}/u', $text);
        if ($letters === 0) {
            return false;
        }

        // 2. Tokenize lowercase words
        $words = preg_split('/[\s\p{P}\x{00A0}]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY);
        if (empty($words)) {
            return false;
        }

        $coreMarkerHits = 0;
        $phoneticPatternHits = 0;

        foreach ($words as $word) {
            if (in_array($word, self::CORE_BANGLISH_MARKERS, true)) {
                $coreMarkerHits++;
            } else if ($this->matchesBanglishPhonetics($word)) {
                $phoneticPatternHits++;
            }
        }

        // It is Banglish if it contains at least 1 core Banglish marker or 2 phonetic patterns
        return $coreMarkerHits >= 1 || $phoneticPatternHits >= 2;
    }

    /**
     * Transliterates Banglish text into standard Bangla script (Unicode Bengali).
     */
    public function transliterate(string $text): string
    {
        if (config('ai.enable_llm_banglish_translation', true) && $this->translator) {
            return $this->llmTransliterate($text);
        }

        return $this->dictionaryTransliterate($text);
    }

    private function llmTransliterate(string $text): string
    {
        return Cache::remember("banglish_translate:" . md5($text), now()->addDays(30), function () use ($text) {
            $prompt = "You are a strict transliterator. Convert the provided Banglish text into standard Bengali script. Output ONLY the translated Bengali text without any conversational filler, quotes, or formatting.";
            $response = $this->translator->complete($prompt, $text);
            return trim($response);
        });
    }

    public function dictionaryTransliterate(string $text): string
    {
        $tokens = preg_split('/(\s+|[^\p{L}\p{N}]+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $result = [];

        foreach ($tokens as $token) {
            $lower = mb_strtolower($token);

            if (isset(self::BANGLISH_DICTIONARY[$lower])) {
                $result[] = self::BANGLISH_DICTIONARY[$lower];
            } else if ($this->isPureWord($token)) {
                $result[] = $this->phoneticTransliterateWord($token);
            } else {
                $result[] = $token;
            }
        }

        return trim(implode('', $result));
    }

    /**
     * Normalizes a query into a structured multi-vector payload.
     *
     * @return array{lang: string, original: string, transliterated: ?string}
     */
    public function normalize(string $text): array
    {
        if (preg_match('/[\x{0980}-\x{09FF}]/u', $text)) {
            return [
                'lang'           => 'bn',
                'original'       => $text,
                'transliterated' => null,
            ];
        }

        if ($this->isBanglish($text)) {
            return [
                'lang'           => 'banglish',
                'original'       => $text,
                'transliterated' => $this->transliterate($text),
            ];
        }

        return [
            'lang'           => 'en',
            'original'       => $text,
            'transliterated' => null,
        ];
    }

    private function isPureWord(string $token): bool
    {
        return preg_match('/^\p{L}+$/u', $token) === 1;
    }

    private function matchesBanglishPhonetics(string $word): bool
    {
        return (bool) preg_match('/(sena|shon|shos|shha|chhe|khon|khule|korb|jabe|parb|babe|dder)$/i', $word);
    }

    private function phoneticTransliterateWord(string $word): string
    {
        $w = mb_strtolower($word);

        $rules = [
            'kivabe' => 'কিভাবে',
            'sh'     => 'শ',
            'ch'     => 'চ',
            'kh'     => 'খ',
            'gh'     => 'ঘ',
            'bh'     => 'ভ',
            'th'     => 'থ',
            'dh'     => 'ধ',
            'ph'     => 'ফ',
            'ng'     => 'ং',
            'aa'     => 'া',
            'ee'     => 'ী',
            'oo'     => 'ূ',
            'ou'     => 'ৌ',
            'oi'     => 'ৈ',
            'a'      => 'া',
            'b'      => 'ব',
            'c'      => 'ক',
            'd'      => 'দ',
            'e'      => 'ে',
            'f'      => 'ফ',
            'g'      => 'গ',
            'h'      => 'হ',
            'i'      => 'ি',
            'j'      => 'জ',
            'k'      => 'ক',
            'l'      => 'ল',
            'm'      => 'ম',
            'n'      => 'ন',
            'o'      => 'ো',
            'p'      => 'প',
            'r'      => 'র',
            's'      => 'স',
            't'      => 'ত',
            'u'      => 'ু',
            'v'      => 'ভ',
            'w'      => 'ওয়া',
            'y'      => 'য়',
            'z'      => 'জ',
        ];

        return strtr($w, $rules);
    }
}
