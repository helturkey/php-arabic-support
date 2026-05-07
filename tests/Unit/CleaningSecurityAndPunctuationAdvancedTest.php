<?php

declare(strict_types=1);

namespace ArabicSupport\Tests\Unit;

use ArabicSupport\Arabic;
use ArabicSupport\Cleaning\UnicodeSecurityCleaner;
use PHPUnit\Framework\TestCase;

final class CleaningSecurityAndPunctuationAdvancedTest extends TestCase
{
    public function test_sanitize_is_conservative_while_sanitize_for_search_is_aggressive(): void
    {
        $input = '<b>إدارةُ المبيعات، iPhone X؟</b>';

        $this->assertSame('إدارةُ المبيعات، iPhone X؟', Arabic::sanitize($input));
        $this->assertSame('إدارة المبيعات، iPhone X؟', Arabic::sanitizePlain($input));
        $this->assertSame('اداره المبيعات iphone x', Arabic::sanitizeForSearch($input));
    }

    public function test_clean_preserves_readable_text_but_removes_html_and_controls(): void
    {
        $input = "<p>مرحبًا\u{200B}\u{202E} بالعالم</p>";

        $this->assertSame('مرحبًا بالعالم', Arabic::clean($input));
    }

    public function test_remove_invisible_removes_zero_width_bidi_and_control_characters(): void
    {
        $input = "أحمد\u{200B}\u{202E}\x07 علي";

        $this->assertSame('أحمد علي', Arabic::removeInvisible($input));
    }

    public function test_security_clean_keeps_new_lines_by_default(): void
    {
        $input = "سطر أول\nسطر\u{200B} ثان";

        $this->assertSame("سطر أول\nسطر ثان", (new UnicodeSecurityCleaner)->clean($input));
    }

    public function test_ordered_list_prefixes_do_not_remove_plain_text_lines(): void
    {
        $input = "أولا بدون رقم\n1. إنشاء حساب\n٢- تأكيد البريد\n(۳) تفعيل الوصول";

        $this->assertSame(
            "أولا بدون رقم\nإنشاء حساب\nتأكيد البريد\nتفعيل الوصول",
            Arabic::stripOrderedListPrefixes($input),
        );
    }

    public function test_punctuation_spacing_handles_arabic_and_english_marks(): void
    {
        $this->assertSame(
            'مرحبًا، كيف الحال؟ جيد: نعم... تمام',
            Arabic::fixPunctuation('مرحبًا،كيف الحال؟جيد:نعم...تمام'),
        );
    }

    public function test_conjunction_waw_normalization_only_for_standalone_waw(): void
    {
        $this->assertSame('أيمن وأحمد ووافق', Arabic::normalizeConjunctionWaw('أيمن و أحمد ووافق'));
    }
}
