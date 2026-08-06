<?php

namespace Tests\Unit;

use App\Services\Faq\BanglishNormalizer;
use App\Services\Faq\LanguageDetector;
use Tests\TestCase;

class BanglishNormalizerTest extends TestCase
{
    private BanglishNormalizer $normalizer;
    private LanguageDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new BanglishNormalizer();
        $this->detector = new LanguageDetector($this->normalizer);
    }

    public function test_detects_banglish_queries(): void
    {
        $this->assertTrue($this->normalizer->isBanglish('amar connection slow keno'));
        $this->assertTrue($this->normalizer->isBanglish('amar bill missing notification solution ki'));
        $this->assertTrue($this->normalizer->isBanglish('kivabe refund request korbo'));
    }

    public function test_distinguishes_english_and_bangla(): void
    {
        $this->assertEquals('bn', $this->detector->detect('আমার কানেকশন স্লো কেন?'));
        $this->assertEquals('banglish', $this->detector->detect('amar connection slow keno'));
        $this->assertEquals('en', $this->detector->detect('How to request a refund in AV-CRM?'));
    }

    public function test_transliterates_banglish_to_bangla(): void
    {
        $transliterated = $this->normalizer->transliterate('amar connection slow keno');
        $this->assertStringContainsString('আমার', $transliterated);
        $this->assertStringContainsString('কেন', $transliterated);
    }
}
