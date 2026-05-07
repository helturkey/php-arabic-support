<?php

declare(strict_types=1);

namespace ArabicSupport\Tests\Unit;

use ArabicSupport\Arabic;
use ArabicSupport\Cleaning\UnicodeSecurityCleaner;
use ArabicSupport\Patterns\ArabicPatterns;
use PHPUnit\Framework\TestCase;

final class UnicodeSecurityAndPatternsTest extends TestCase
{
    public function test_patterns_detect_arabic_script(): void
    {
        $this->assertSame(1, preg_match(ArabicPatterns::arabic(), 'Order رقم 123'));
        $this->assertSame(0, preg_match(ArabicPatterns::arabic(), 'Order 123'));
    }

    public function test_patterns_validate_unicode_slug(): void
    {
        $this->assertSame(1, preg_match(ArabicPatterns::slug(), 'دليل-المستخدم-2026'));
        $this->assertSame(0, preg_match(ArabicPatterns::slug(), 'دليل المستخدم!'));
    }

    public function test_security_cleaner_detects_and_removes_bidi_controls(): void
    {
        $text = "abc\u{202E}def";
        $cleaner = new UnicodeSecurityCleaner;

        $this->assertTrue($cleaner->hasBidiControls($text));
        $this->assertSame('abcdef', $cleaner->removeBidiControls($text));
    }

    public function test_security_cleaner_detects_zero_width_and_control_characters(): void
    {
        $text = "أحمد\u{200B}\u{0007} علي";
        $cleaner = new UnicodeSecurityCleaner;

        $this->assertTrue($cleaner->hasInvisibleCharacters($text));
        $this->assertSame('أحمد علي', $cleaner->clean($text));
    }

    public function test_security_policy_removes_hidden_unicode_but_keeps_spelling(): void
    {
        $text = "إدارة\u{200F} المنتجات";

        $this->assertSame('إدارة المنتجات', Arabic::securityClean($text));
    }

    public function test_control_cleaner_can_keep_normal_new_lines(): void
    {
        $text = "سطر أول\nسطر\u{0007} ثاني";

        $this->assertSame("سطر أول\nسطر ثاني", (new UnicodeSecurityCleaner)->removeControlCharacters($text));
    }
}
