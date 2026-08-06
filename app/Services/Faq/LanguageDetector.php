<?php

namespace App\Services\Faq;

class LanguageDetector
{
    public function __construct(
        private readonly ?BanglishNormalizer $normalizer = null
    ) {}

    /**
     * Detects language type:
     * - 'bn': Native Bangla Unicode script
     * - 'banglish': Bangla written in Latin/English script
     * - 'en': Standard English
     */
    public function detect(string $text): string
    {
        $totalLetters = preg_match_all('/\p{L}/u', $text);

        if ($totalLetters === 0) {
            return 'en'; // No letters at all — safe default
        }

        $banglaLetters = preg_match_all('/[\x{0980}-\x{09FF}]/u', $text);

        // 1. Native Bangla Unicode script check
        if (($banglaLetters / $totalLetters) > 0.3) {
            return 'bn';
        }

        // 2. Banglish detection layer
        if (config('ai.enable_banglish_normalizer', true)) {
            $normalizer = $this->normalizer ?? app(BanglishNormalizer::class);
            if ($normalizer->isBanglish($text)) {
                return 'banglish';
            }
        }

        return 'en';
    }
}