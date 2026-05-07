<?php

declare(strict_types=1);

namespace ArabicSupport\Tests\Unit;

use ArabicSupport\Arabic;
use ArabicSupport\Enums\DigitSet;
use ArabicSupport\Enums\LengthUnit;
use PHPUnit\Framework\TestCase;

/**
 * Broad Arabic scenarios collected to expose practical edge cases in slugs,
 * search keys, filenames, excerpts, punctuation, and mixed-script text.
 */
final class RealWorldArabicCasesTest extends TestCase
{
    public function test_mixed_product_title_slug_keeps_arabic_and_latin_readable(): void
    {
        $input = 'هاتف iPhone 15 Pro Max — نسخة الشرق الأوسط';

        $this->assertSame(
            'هاتف-iphone-15-pro-max-نسخة-الشرق-الأوسط',
            Arabic::unicodeSlug($input, maxWords: 10),
        );
    }

    public function test_report_filename_removes_path_separators_and_keeps_extension(): void
    {
        $this->assertSame(
            'تقرير-المبيعات-الربع-الأول-2026.pdf',
            Arabic::safeFilename('تقرير: المبيعات/الربع الأول؟ ٢٠٢٦.pdf'),
        );
    }

    public function test_ordered_list_prefixes_support_arabic_latin_and_eastern_digits(): void
    {
        $input = "1. إنشاء حساب\n٢- تأكيد البريد\n(۳) تفعيل الوصول";

        $this->assertSame(
            "إنشاء حساب\nتأكيد البريد\nتفعيل الوصول",
            Arabic::stripOrderedListPrefixes($input),
        );
    }

    public function test_sanitize_is_conservative_but_search_sanitize_is_aggressive(): void
    {
        $input = '<b>إدارةُ المبيعات، iPhone X؟</b>';

        $this->assertSame('إدارةُ المبيعات، iPhone X؟', Arabic::sanitize($input));
        $this->assertSame('اداره المبيعات iphone x', Arabic::sanitizeForSearch($input));
    }

    public function test_punctuation_spacing_handles_arabic_and_english_marks(): void
    {
        $input = 'مرحبًا،كيف الحال؟جيد:نعم...تمام';

        $this->assertSame('مرحبًا، كيف الحال؟ جيد: نعم... تمام', Arabic::fixPunctuation($input));
    }

    public function test_digits_can_normalize_mixed_digit_sets(): void
    {
        $input = 'فاتورة ١٢٣ و کد ۴۵۶ و رقم 789';

        $this->assertSame('فاتورة 123 و کد 456 و رقم 789', Arabic::normalizeDigits($input, DigitSet::Latin));
        $this->assertSame('فاتورة ١٢٣ و کد ٤٥٦ و رقم ٧٨٩', Arabic::normalizeDigits($input, DigitSet::ArabicIndic));
    }

    public function test_excerpt_does_not_cut_words_and_keeps_suffix_inside_limit(): void
    {
        $input = 'هذا نص عربي طويل لاختبار إنشاء مقتطفات عامة من محتوى المستخدم';

        $this->assertSame('هذا نص عربي...', Arabic::limit($input, 15, LengthUnit::Unicode, '...'));
    }

    public function test_grapheme_limit_keeps_combining_mark_with_base_letter(): void
    {
        $input = 'مُحَمَّد علي';

        $this->assertSame('مُحَ...', Arabic::limit($input, 5, LengthUnit::Grapheme, '...'));
    }

    public function test_name_normalizer_does_not_change_alef_maqsura_unless_requested(): void
    {
        $this->assertSame('عيسى', Arabic::name('عيسى'));
        $this->assertSame('عيسي', Arabic::name('عيسى', normalizeAlefMaqsura: true));
    }

    public function test_bad_words_can_be_supplied_as_explicit_project_list(): void
    {
        $this->assertTrue(Arabic::containsBadWords('هذا نص ممنوع هنا', ['ممنوع']));
        $this->assertFalse(Arabic::containsBadWords('هذا نص مقبول هنا', ['ممنوع']));
    }
}
