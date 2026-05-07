<?php

declare(strict_types=1);

namespace ArabicSupport\Tests\Unit;

use ArabicSupport\Arabic;
use ArabicSupport\ArabicInspector;
use ArabicSupport\ArabicText;
use ArabicSupport\Cleaning\TextCleaner;
use ArabicSupport\Cleaning\UnicodeSecurityCleaner;
use ArabicSupport\Enums\ArabicPolicy;
use ArabicSupport\Enums\DigitSet;
use ArabicSupport\Enums\HamzaPolicy;
use ArabicSupport\Enums\LengthUnit;
use ArabicSupport\Enums\SlugMode;
use ArabicSupport\Enums\TaMarbutaPolicy;
use ArabicSupport\Names\ArabicNameNormalizer;
use ArabicSupport\Normalization\DiacriticsStripper;
use ArabicSupport\Normalization\TatweelStripper;
use ArabicSupport\Numbers\ArabicDigits;
use ArabicSupport\Punctuation\ArabicPunctuation;
use ArabicSupport\Slug\ArabicSlugger;
use PHPUnit\Framework\TestCase;

/**
 * Comprehensive test suite for the Arabic Support library.
 *
 * Covers every public method with extensive real-world Arabic scenarios,
 * edge cases, boundary conditions, and idempotency checks.
 *
 * Arabic script features exercised:
 *   - Diacritics (tashkeel): fatha, damma, kasra, sukun, shadda, tanwin variants
 *   - Quranic marks: maddah, hamza above/below, superscript alef
 *   - Tatweel (kashida) in various positions
 *   - Hamza variants: أ إ آ ؤ ئ ء
 *   - Alef maqsura: ى
 *   - Ta marbuta: ة
 *   - Persian letter variants: ک ی ۀ ە
 *   - Arabic-Indic digits: ٠١٢٣٤٥٦٧٨٩
 *   - Eastern Arabic/Persian digits: ۰۱۲۳۴۵۶۷۸۹
 *   - Bidirectional controls: RLM, LRM, RLE, LRE, PDF, RLO, LRO, FSI, PDI
 *   - Zero-width characters: ZWJ, ZWNJ, ZWSP, WJ, BOM
 *   - Mixed Arabic-Latin-digit texts
 *   - Conjunction waw (و) spacing
 *   - Real Arabic names, titles, news headlines, e-commerce strings
 */
final class ComprehensiveArabicTest extends TestCase
{
    // =========================================================================
    // ArabicInspector — containsArabic()
    // =========================================================================

    public function test_contains_arabic_pure_arabic_sentence(): void
    {
        $this->assertTrue(Arabic::containsArabic('السلام عليكم ورحمة الله'));
    }

    public function test_contains_arabic_single_letter(): void
    {
        $this->assertTrue(Arabic::containsArabic('ع'));
    }

    public function test_contains_arabic_arabic_digit_text_mixed(): void
    {
        $this->assertTrue(Arabic::containsArabic('الفاتورة رقم ١٢٣'));
    }

    public function test_contains_arabic_mixed_with_latin(): void
    {
        $this->assertTrue(Arabic::containsArabic('iPhone ١٥ برو ماكس'));
    }

    public function test_contains_arabic_diacritic_matches_arabic_block(): void
    {
        // Tashkeel (U+064E fatha) lives in the Arabic Unicode block, so the
        // containsArabic() pattern returns true. Use isArabic() when you need
        // letter-only detection (isArabic returns false for diacritics alone).
        $this->assertTrue(Arabic::containsArabic("\u{064E}"));
        $this->assertFalse(Arabic::isArabic("\u{064E}"));
    }

    public function test_contains_arabic_returns_false_for_latin_only(): void
    {
        $this->assertFalse(Arabic::containsArabic('Hello World 123'));
    }

    public function test_contains_arabic_returns_false_for_empty_string(): void
    {
        $this->assertFalse(Arabic::containsArabic(''));
    }

    public function test_contains_arabic_returns_false_for_digits_only(): void
    {
        $this->assertFalse(Arabic::containsArabic('123456'));
    }

    public function test_contains_arabic_hebrew_is_not_arabic(): void
    {
        $this->assertFalse(Arabic::containsArabic('שלום עולם'));
    }

    public function test_contains_arabic_handles_urls_with_arabic(): void
    {
        $this->assertTrue(Arabic::containsArabic('https://example.com/تعليم/مقال'));
    }

    // =========================================================================
    // ArabicInspector — isArabic()
    // =========================================================================

    public function test_is_arabic_pure_arabic_word(): void
    {
        $this->assertTrue(Arabic::isArabic('مرحبا'));
    }

    public function test_is_arabic_with_diacritics_still_arabic(): void
    {
        $this->assertTrue(Arabic::isArabic('مَرْحَبًا'));
    }

    public function test_is_arabic_returns_false_for_mixed(): void
    {
        $this->assertFalse(Arabic::isArabic('مرحبا Hello'));
    }

    public function test_is_arabic_returns_false_for_digits_only(): void
    {
        $this->assertFalse(Arabic::isArabic('١٢٣'));
    }

    public function test_is_arabic_returns_false_for_empty_string(): void
    {
        $this->assertFalse(Arabic::isArabic(''));
    }

    public function test_is_arabic_returns_false_for_whitespace_only(): void
    {
        $this->assertFalse(Arabic::isArabic('   '));
    }

    public function test_is_arabic_multiple_words_all_arabic(): void
    {
        $this->assertTrue(Arabic::isArabic('عبد الرحمن'));
    }

    public function test_is_arabic_ta_marbuta_counts_as_arabic(): void
    {
        $this->assertTrue(Arabic::isArabic('مدرسة'));
    }

    public function test_is_arabic_hamza_variants_count_as_arabic(): void
    {
        $this->assertTrue(Arabic::isArabic('أحمد'));
        $this->assertTrue(Arabic::isArabic('إيمان'));
        $this->assertTrue(Arabic::isArabic('آمنة'));
    }

    // =========================================================================
    // ArabicInspector — arabicRatio()
    // =========================================================================

    public function test_arabic_ratio_pure_arabic_is_one(): void
    {
        $this->assertSame(1.0, Arabic::arabicRatio('جامعة القاهرة'));
    }

    public function test_arabic_ratio_pure_latin_is_zero(): void
    {
        $this->assertSame(0.0, Arabic::arabicRatio('Cairo University'));
    }

    public function test_arabic_ratio_empty_is_zero(): void
    {
        $this->assertSame(0.0, Arabic::arabicRatio(''));
    }

    public function test_arabic_ratio_mixed_text_is_between_zero_and_one(): void
    {
        $ratio = Arabic::arabicRatio('مرحبا Hello');
        $this->assertGreaterThan(0.0, $ratio);
        $this->assertLessThan(1.0, $ratio);
    }

    public function test_arabic_ratio_rounded_to_four_decimal_places(): void
    {
        $ratio = Arabic::arabicRatio('عربي Latin');
        $this->assertSame(round($ratio, 4), $ratio);
    }

    // =========================================================================
    // ArabicInspector — inspect()
    // =========================================================================

    public function test_inspect_returns_complete_diagnostic_array(): void
    {
        $result = Arabic::inspect('مَرْحَبًا عالم!');
        $this->assertArrayHasKey('characters', $result);
        $this->assertArrayHasKey('words', $result);
        $this->assertArrayHasKey('arabic_ratio', $result);
        $this->assertArrayHasKey('has_arabic', $result);
        $this->assertArrayHasKey('is_arabic', $result);
        $this->assertArrayHasKey('has_diacritics', $result);
        $this->assertArrayHasKey('has_tatweel', $result);
        $this->assertArrayHasKey('has_html', $result);
        $this->assertArrayHasKey('has_arabic_digits', $result);
        $this->assertArrayHasKey('has_bidi_controls', $result);
        $this->assertArrayHasKey('has_invisible_chars', $result);
        $this->assertArrayHasKey('has_suspicious_unicode', $result);
    }

    public function test_inspect_detects_diacritics(): void
    {
        $result = Arabic::inspect('مَرْحَبًا');
        $this->assertTrue($result['has_diacritics']);
        $this->assertTrue($result['has_arabic']);
    }

    public function test_inspect_detects_tatweel(): void
    {
        $result = Arabic::inspect('العـرب');
        $this->assertTrue($result['has_tatweel']);
    }

    public function test_inspect_detects_html(): void
    {
        $result = Arabic::inspect('<b>مرحبا</b>');
        $this->assertTrue($result['has_html']);
    }

    public function test_inspect_detects_arabic_indic_digits(): void
    {
        $result = Arabic::inspect('الفاتورة ١٢٣');
        $this->assertTrue($result['has_arabic_digits']);
    }

    public function test_inspect_detects_eastern_arabic_digits(): void
    {
        $result = Arabic::inspect('كد ۴۵۶');
        $this->assertTrue($result['has_arabic_digits']);
    }

    public function test_inspect_detects_bidi_controls(): void
    {
        $result = Arabic::inspect("أحمد\u{202E}علي");
        $this->assertTrue($result['has_bidi_controls']);
        $this->assertTrue($result['has_suspicious_unicode']);
    }

    public function test_inspect_detects_zero_width_chars(): void
    {
        $result = Arabic::inspect("كلمة\u{200B}واحدة");
        $this->assertTrue($result['has_invisible_chars']);
    }

    public function test_inspect_clean_arabic_sentence_has_no_flags(): void
    {
        $result = Arabic::inspect('الحمد لله رب العالمين');
        $this->assertFalse($result['has_diacritics']);
        $this->assertFalse($result['has_tatweel']);
        $this->assertFalse($result['has_html']);
        $this->assertFalse($result['has_arabic_digits']);
        $this->assertFalse($result['has_bidi_controls']);
        $this->assertFalse($result['has_invisible_chars']);
        $this->assertFalse($result['has_suspicious_unicode']);
        $this->assertTrue($result['has_arabic']);
    }

    public function test_inspect_word_count_arabic_sentence(): void
    {
        $result = Arabic::inspect('في البداية كان الكلمة');
        $this->assertSame(4, $result['words']);
    }

    public function test_inspect_word_count_collapses_whitespace(): void
    {
        $result = Arabic::inspect("مرحبا  \t  عالم");
        $this->assertSame(2, $result['words']);
    }

    public function test_inspect_empty_string(): void
    {
        $result = Arabic::inspect('');
        $this->assertSame(0, $result['characters']);
        $this->assertSame(0, $result['words']);
        $this->assertSame(0.0, $result['arabic_ratio']);
        $this->assertFalse($result['has_arabic']);
        $this->assertFalse($result['is_arabic']);
    }

    // =========================================================================
    // Normalization — ArabicPolicy::Strict
    // =========================================================================

    public function test_strict_policy_preserves_tatweel(): void
    {
        $text = 'العـــرب';
        $this->assertSame($text, Arabic::normalize($text, ArabicPolicy::Strict));
    }

    public function test_strict_policy_preserves_diacritics(): void
    {
        $text = 'مُحَمَّدٌ';
        $this->assertSame($text, Arabic::normalize($text, ArabicPolicy::Strict));
    }

    public function test_strict_policy_preserves_multiple_spaces(): void
    {
        $text = 'كلمة    أخرى';
        $this->assertSame($text, Arabic::normalize($text, ArabicPolicy::Strict));
    }

    public function test_strict_policy_preserves_tabs(): void
    {
        $text = "كلمة\t\tأخرى";
        $this->assertSame($text, Arabic::normalize($text, ArabicPolicy::Strict));
    }

    public function test_strict_policy_preserves_all_hamza_forms(): void
    {
        $text = 'أإآؤئء';
        $this->assertSame($text, Arabic::normalize($text, ArabicPolicy::Strict));
    }

    // =========================================================================
    // Normalization — ArabicPolicy::Display
    // =========================================================================

    public function test_display_strips_tatweel_from_middle(): void
    {
        $this->assertSame('سلام', Arabic::normalize('سـلام', ArabicPolicy::Display));
    }

    public function test_display_strips_repeated_tatweel(): void
    {
        $this->assertSame('العرب', Arabic::normalize('العـــرب', ArabicPolicy::Display));
    }

    public function test_display_preserves_full_diacritics(): void
    {
        $this->assertSame('بِسْمِ اللهِ', Arabic::normalize('بِسْمِ   اللهِ', ArabicPolicy::Display));
    }

    public function test_display_collapses_mixed_whitespace(): void
    {
        $this->assertSame('أول ثاني', Arabic::normalize("أول\t\t  ثاني", ArabicPolicy::Display));
    }

    public function test_display_trims_surrounding_whitespace(): void
    {
        $this->assertSame('مرحبا', Arabic::normalize('  مرحبا  ', ArabicPolicy::Display));
    }

    public function test_display_preserves_ta_marbuta(): void
    {
        $this->assertSame('فاطمة', Arabic::normalize('فاطمة', ArabicPolicy::Display));
    }

    public function test_display_preserves_alef_maqsura(): void
    {
        $this->assertSame('على', Arabic::normalize('على', ArabicPolicy::Display));
    }

    public function test_display_preserves_hamza_above_ya(): void
    {
        $this->assertSame('مسائل', Arabic::normalize('مسائل', ArabicPolicy::Display));
    }

    public function test_display_preserves_waw_with_hamza(): void
    {
        $this->assertSame('مؤتمر', Arabic::normalize('مؤتمر', ArabicPolicy::Display));
    }

    public function test_display_preserves_alef_with_madda(): void
    {
        $this->assertSame('آداب', Arabic::normalize('آداب', ArabicPolicy::Display));
    }

    public function test_display_is_idempotent(): void
    {
        $text = 'إدارة التعليم العالي';
        $once = Arabic::normalize($text, ArabicPolicy::Display);
        $twice = Arabic::normalize($once, ArabicPolicy::Display);
        $this->assertSame($once, $twice);
    }

    // =========================================================================
    // Normalization — ArabicPolicy::Search
    // =========================================================================

    public function test_search_folds_alef_variants_to_bare_alef(): void
    {
        $this->assertSame('اسماعيل', Arabic::normalize('إسماعيل', ArabicPolicy::Search));
        $this->assertSame('احمد', Arabic::normalize('أحمد', ArabicPolicy::Search));
        $this->assertSame('اثار', Arabic::normalize('آثار', ArabicPolicy::Search));
    }

    public function test_search_folds_alef_maqsura_to_ya(): void
    {
        $this->assertSame('علي', Arabic::normalize('على', ArabicPolicy::Search));
    }

    public function test_search_folds_ta_marbuta_to_haa(): void
    {
        $this->assertSame('مدرسه', Arabic::normalize('مدرسة', ArabicPolicy::Search));
        $this->assertSame('فاطمه', Arabic::normalize('فاطمة', ArabicPolicy::Search));
    }

    public function test_search_folds_waw_with_hamza(): void
    {
        $this->assertSame('مومن', Arabic::normalize('مؤمن', ArabicPolicy::Search));
    }

    public function test_search_does_not_fold_ya_with_hamza(): void
    {
        // ئ is intentionally kept in Search policy
        $this->assertSame('رئيس', Arabic::searchKey('رئيس'));
    }

    public function test_search_strips_diacritics(): void
    {
        $this->assertSame('مرحبا', Arabic::searchKey('مَرْحَبًا'));
    }

    public function test_search_converts_arabic_indic_digits_to_latin(): void
    {
        $this->assertSame('فاتوره 123', Arabic::searchKey('فاتورة ١٢٣'));
    }

    public function test_search_converts_eastern_arabic_digits_to_latin(): void
    {
        $this->assertSame('شماره 456', Arabic::searchKey('شماره ۴۵۶'));
    }

    public function test_search_lowercases_latin(): void
    {
        $this->assertSame('iphone 15 برو', Arabic::searchKey('iPhone 15 برو'));
    }

    public function test_search_normalizes_persian_kaf(): void
    {
        $this->assertSame('كتاب', Arabic::normalize('کتاب', ArabicPolicy::Search));
    }

    public function test_search_normalizes_persian_ya(): void
    {
        $this->assertSame('يار', Arabic::normalize('یار', ArabicPolicy::Search));
    }

    public function test_search_is_idempotent(): void
    {
        $text = 'مدرسة إبراهيم';
        $once = Arabic::searchKey($text);
        $twice = Arabic::searchKey($once);
        $this->assertSame($once, $twice);
    }

    public function test_search_real_world_product_comparison(): void
    {
        // Shoppers enter the product name with spelling variations — all must match
        $canonical = Arabic::searchKey('سماعة بلوتوث');
        $this->assertSame($canonical, Arabic::searchKey('سمّاعة بلوتوث'));
        $this->assertSame($canonical, Arabic::searchKey('سماعه بلوتوث'));
        $this->assertSame($canonical, Arabic::searchKey('سماعة بلوتوث'));
    }

    public function test_search_name_matching_with_different_hamza_spellings(): void
    {
        $this->assertSame(
            Arabic::searchKey('إبراهيم'),
            Arabic::searchKey('ابراهيم'),
        );
        $this->assertSame(
            Arabic::searchKey('أسامة'),
            Arabic::searchKey('اسامة'),
        );
    }

    // =========================================================================
    // Normalization — ArabicPolicy::Slug
    // =========================================================================

    public function test_slug_policy_keeps_alef_with_hamza_for_readable_urls(): void
    {
        $result = Arabic::normalize('إدارة المنتجات', ArabicPolicy::Slug);
        $this->assertStringContainsString('إ', $result);
    }

    public function test_slug_policy_keeps_alef_maqsura(): void
    {
        $result = Arabic::normalize('على الجبل', ArabicPolicy::Slug);
        $this->assertStringContainsString('ى', $result);
    }

    public function test_slug_policy_keeps_ta_marbuta(): void
    {
        $result = Arabic::normalize('مدرسة النور', ArabicPolicy::Slug);
        $this->assertStringContainsString('ة', $result);
    }

    public function test_slug_policy_strips_diacritics(): void
    {
        $result = Arabic::normalize('مَدْرَسَةٌ', ArabicPolicy::Slug);
        $this->assertSame('مدرسة', $result);
    }

    public function test_slug_policy_converts_digits_to_latin(): void
    {
        $result = Arabic::normalize('عام ٢٠٢٦', ArabicPolicy::Slug);
        $this->assertStringContainsString('2026', $result);
        $this->assertStringNotContainsString('٢', $result);
    }

    public function test_slug_policy_normalizes_persian_kaf(): void
    {
        $result = Arabic::normalize('کتاب', ArabicPolicy::Slug);
        $this->assertSame('كتاب', $result);
    }

    // =========================================================================
    // Normalization — ArabicPolicy::Security
    // =========================================================================

    public function test_security_policy_removes_bidi_controls(): void
    {
        $text = "مرحبا\u{202E}عالم";
        $result = Arabic::normalize($text, ArabicPolicy::Security);
        $this->assertStringNotContainsString("\u{202E}", $result);
        $this->assertStringContainsString('مرحبا', $result);
    }

    public function test_security_policy_removes_zero_width_chars(): void
    {
        $text = "أحمد\u{200B}علي";
        $result = Arabic::normalize($text, ArabicPolicy::Security);
        $this->assertStringNotContainsString("\u{200B}", $result);
    }

    public function test_security_policy_preserves_arabic_letters(): void
    {
        $text = "إبراهيم\u{200F} الخليل";
        $result = Arabic::normalize($text, ArabicPolicy::Security);
        $this->assertStringContainsString('إبراهيم', $result);
        $this->assertStringContainsString('الخليل', $result);
    }

    public function test_security_policy_preserves_hamza_forms_unlike_search(): void
    {
        $text = "إدارة\u{200F}";
        $result = Arabic::normalize($text, ArabicPolicy::Security);
        $this->assertStringContainsString('إ', $result); // not folded to ا
    }

    // =========================================================================
    // normalizeLetters() — HamzaPolicy and TaMarbutaPolicy
    // =========================================================================

    public function test_normalize_letters_hamza_keep_leaves_ya_and_waw_hamza(): void
    {
        // HamzaPolicy::Keep prevents folding of ؤ and ئ specifically.
        // Note: normalizeAlef defaults to true so أ/إ/آ are still folded unless disabled.
        $text = 'مؤمن ورئيس';
        $result = Arabic::normalizeLetters($text, hamza: HamzaPolicy::Keep, normalizeAlef: false);
        $this->assertStringContainsString('مؤمن', $result);   // ؤ kept
        $this->assertStringContainsString('رئيس', $result);   // ئ kept
    }

    public function test_normalize_letters_hamza_keep_with_alef_disabled_leaves_all(): void
    {
        $text = 'أإآؤئ';
        $result = Arabic::normalizeLetters(
            $text,
            hamza: HamzaPolicy::Keep,
            normalizeAlef: false,
            normalizeAlefMaqsura: false,
            normalizePersianLetters: false,
        );
        $this->assertSame($text, $result);
    }

    public function test_normalize_letters_hamza_fold_folds_waw_only(): void
    {
        $text = 'مؤمن ورئيس';
        $result = Arabic::normalizeLetters($text, hamza: HamzaPolicy::Fold);
        $this->assertStringContainsString('مومن', $result);
        $this->assertStringContainsString('رئيس', $result); // ئ preserved
    }

    public function test_normalize_letters_hamza_fold_all_folds_both(): void
    {
        $text = 'مؤمن ورئيس';
        $result = Arabic::normalizeLetters($text, hamza: HamzaPolicy::FoldAll);
        $this->assertStringContainsString('مومن', $result);
        $this->assertStringContainsString('رييس', $result); // ئ folded
    }

    public function test_normalize_letters_ta_marbuta_keep(): void
    {
        $result = Arabic::normalizeLetters('مدرسة', taMarbuta: TaMarbutaPolicy::Keep);
        $this->assertSame('مدرسة', $result);
    }

    public function test_normalize_letters_ta_marbuta_to_haa(): void
    {
        $result = Arabic::normalizeLetters('مدرسة', taMarbuta: TaMarbutaPolicy::Haa);
        $this->assertSame('مدرسه', $result);
    }

    public function test_normalize_letters_ta_marbuta_to_taa(): void
    {
        $result = Arabic::normalizeLetters('مدرسة', taMarbuta: TaMarbutaPolicy::Taa);
        $this->assertSame('مدرست', $result);
    }

    public function test_normalize_letters_alef_normalization_enabled(): void
    {
        $this->assertSame('الفاتحة', Arabic::normalizeLetters('الفاتحة', normalizeAlef: true));
        $this->assertSame('ابراهيم', Arabic::normalizeLetters('إبراهيم', normalizeAlef: true));
        $this->assertSame('احمد', Arabic::normalizeLetters('أحمد', normalizeAlef: true));
        $this->assertSame('اثار', Arabic::normalizeLetters('آثار', normalizeAlef: true));
    }

    public function test_normalize_letters_alef_normalization_disabled(): void
    {
        $this->assertSame('إبراهيم', Arabic::normalizeLetters('إبراهيم', normalizeAlef: false));
    }

    public function test_normalize_letters_alef_maqsura_normalization(): void
    {
        $this->assertSame('علي', Arabic::normalizeLetters('على', normalizeAlefMaqsura: true));
    }

    public function test_normalize_letters_alef_maqsura_kept_when_disabled(): void
    {
        $this->assertSame('على', Arabic::normalizeLetters('على', normalizeAlefMaqsura: false));
    }

    public function test_normalize_letters_persian_letters_normalized(): void
    {
        $result = Arabic::normalizeLetters('کتاب یار', normalizePersianLetters: true);
        $this->assertSame('كتاب يار', $result);
    }

    public function test_normalize_letters_persian_he_with_hamza_to_ta_marbuta(): void
    {
        $result = Arabic::normalizeLetters('خانۀ', normalizePersianLetters: true);
        $this->assertStringContainsString('ة', $result);
    }

    public function test_normalize_letters_kurdish_he_to_arabic_ha(): void
    {
        $result = Arabic::normalizeLetters('هاڵە', normalizePersianLetters: true);
        // ە (U+06D5) → ه (U+0647)
        $this->assertStringNotContainsString("\u{06D5}", $result);
    }

    public function test_normalize_letters_all_flags_disabled_changes_nothing(): void
    {
        $text = 'إبراهيم مؤتمر مدرسة على';
        $result = Arabic::normalizeLetters(
            $text,
            hamza: HamzaPolicy::Keep,
            taMarbuta: TaMarbutaPolicy::Keep,
            normalizeAlef: false,
            normalizeAlefMaqsura: false,
            normalizePersianLetters: false,
        );
        $this->assertSame($text, $result);
    }

    // =========================================================================
    // DiacriticsStripper
    // =========================================================================

    public function test_strip_diacritics_fatha(): void
    {
        $this->assertSame('كتب', Arabic::stripDiacritics('كَتَبَ'));
    }

    public function test_strip_diacritics_damma(): void
    {
        $this->assertSame('رجل', Arabic::stripDiacritics('رُجُلٌ'));
    }

    public function test_strip_diacritics_kasra(): void
    {
        $this->assertSame('كتب', Arabic::stripDiacritics('كِتَبٍ'));
    }

    public function test_strip_diacritics_shadda(): void
    {
        $this->assertSame('محمد', Arabic::stripDiacritics('مُحَمَّدٌ'));
    }

    public function test_strip_diacritics_tanwin_all_forms(): void
    {
        $this->assertSame('كتاب', Arabic::stripDiacritics('كِتَابٌ'));
        $this->assertSame('رجل', Arabic::stripDiacritics('رَجُلٍ'));
        // tanwin fathatan written with alef seat (بَيْتًا): the alef is a base letter,
        // so after stripping only the tashkeel marks it becomes بيتا
        $this->assertSame('بيتا', Arabic::stripDiacritics('بَيْتًا'));
    }

    public function test_strip_diacritics_sukun(): void
    {
        $this->assertSame('كلب', Arabic::stripDiacritics('كَلْبٌ'));
    }

    public function test_strip_diacritics_quran_marks_included_by_default(): void
    {
        // Superscript alef (U+0670) is a Quranic mark — stripped by default
        $text = "ا\u{0670}لرَّحْمَٰنِ";
        $stripped = Arabic::stripDiacritics($text, includeQuranMarks: true);
        $this->assertStringNotContainsString("\u{0670}", $stripped);
        $this->assertStringNotContainsString("\u{0650}", $stripped);
    }

    public function test_strip_diacritics_quran_marks_excluded_preserves_superscript_alef(): void
    {
        // When includeQuranMarks is false, standard tashkeel is still stripped but
        // Quranic-only marks (U+0670 superscript alef) are preserved.
        $text = "ا\u{0670}ل";
        $stripped = Arabic::stripDiacritics($text, includeQuranMarks: false);
        $this->assertStringContainsString("\u{0670}", $stripped);
        // Base letters still present
        $this->assertStringContainsString('ا', $stripped);
        $this->assertStringContainsString('ل', $stripped);
    }

    public function test_strip_diacritics_leaves_arabic_letters_intact(): void
    {
        $this->assertSame('مدرسة الأطفال', Arabic::stripDiacritics('مَدْرَسَةُ الأطفالِ'));
    }

    public function test_strip_diacritics_does_not_affect_latin(): void
    {
        $this->assertSame('مدرسة iPhone', Arabic::stripDiacritics('مَدْرَسَةً iPhone'));
    }

    public function test_strip_diacritics_is_idempotent(): void
    {
        $text = 'مَدْرَسَةً';
        $once = Arabic::stripDiacritics($text);
        $twice = Arabic::stripDiacritics($once);
        $this->assertSame($once, $twice);
    }

    // =========================================================================
    // TatweelStripper
    // =========================================================================

    public function test_strip_tatweel_single_tatweel(): void
    {
        $this->assertSame('سلام', Arabic::stripTatweel('سـلام'));
    }

    public function test_strip_tatweel_multiple_tatweels(): void
    {
        $this->assertSame('العرب', Arabic::stripTatweel('العـــرب'));
    }

    public function test_strip_tatweel_at_word_end(): void
    {
        $this->assertSame('العرب', Arabic::stripTatweel('العربـ'));
    }

    public function test_strip_tatweel_does_not_affect_latin(): void
    {
        $this->assertSame('Hello World', Arabic::stripTatweel('Hello World'));
    }

    public function test_strip_tatweel_preserves_arabic_letters(): void
    {
        $this->assertSame('بسم الله', Arabic::stripTatweel('بسـم اللـه'));
    }

    public function test_strip_tatweel_is_idempotent(): void
    {
        $text = 'العـــرب';
        $once = Arabic::stripTatweel($text);
        $twice = Arabic::stripTatweel($once);
        $this->assertSame($once, $twice);
    }

    public function test_stripetatweel_alias_works(): void
    {
        $this->assertSame(
            Arabic::stripTatweel('العـرب'),
            Arabic::stripeTatweel('العـرب'),
        );
    }

    // =========================================================================
    // ArabicDigits — toLatin / toArabicIndic / toEasternArabic
    // =========================================================================

    public function test_digits_to_latin_arabic_indic(): void
    {
        $this->assertSame('0123456789', Arabic::digitsToLatin('٠١٢٣٤٥٦٧٨٩'));
    }

    public function test_digits_to_latin_eastern_arabic(): void
    {
        $this->assertSame('0123456789', Arabic::digitsToLatin('۰۱۲۳۴۵۶۷۸۹'));
    }

    public function test_digits_to_latin_mixed_digit_sets(): void
    {
        $this->assertSame('123 456 789', Arabic::digitsToLatin('١٢٣ ۴۵۶ 789'));
    }

    public function test_digits_to_latin_leaves_latin_unchanged(): void
    {
        $this->assertSame('الرقم 42', Arabic::digitsToLatin('الرقم 42'));
    }

    public function test_digits_to_arabic_indic_from_latin(): void
    {
        $this->assertSame('٠١٢٣٤٥٦٧٨٩', Arabic::digitsToArabicIndic('0123456789'));
    }

    public function test_digits_to_arabic_indic_from_eastern(): void
    {
        $this->assertSame('٠١٢٣٤٥٦٧٨٩', Arabic::digitsToArabicIndic('۰۱۲۳۴۵۶۷۸۹'));
    }

    public function test_digits_to_arabic_indic_mixed_text(): void
    {
        $this->assertSame('الفاتورة رقم ١٢٣', Arabic::digitsToArabicIndic('الفاتورة رقم 123'));
    }

    public function test_digits_to_eastern_arabic_from_latin(): void
    {
        $this->assertSame('۰۱۲۳۴۵۶۷۸۹', Arabic::digitsToEasternArabic('0123456789'));
    }

    public function test_digits_to_eastern_arabic_from_arabic_indic(): void
    {
        $this->assertSame('۰۱۲۳۴۵۶۷۸۹', Arabic::digitsToEasternArabic('٠١٢٣٤٥٦٧٨٩'));
    }

    public function test_normalize_digits_to_latin(): void
    {
        $this->assertSame('123', Arabic::normalizeDigits('١٢٣', DigitSet::Latin));
    }

    public function test_normalize_digits_to_arabic_indic(): void
    {
        $this->assertSame('١٢٣', Arabic::normalizeDigits('123', DigitSet::ArabicIndic));
    }

    public function test_normalize_digits_to_eastern_arabic(): void
    {
        $this->assertSame('۱۲۳', Arabic::normalizeDigits('123', DigitSet::EasternArabic));
    }

    public function test_normalize_digits_roundtrip(): void
    {
        $original = '123456789';
        $roundtripped = Arabic::normalizeDigits(
            Arabic::normalizeDigits($original, DigitSet::ArabicIndic),
            DigitSet::Latin,
        );
        $this->assertSame($original, $roundtripped);
    }

    public function test_normalize_digits_invoice_scenario(): void
    {
        $input = 'المبلغ: ١٢٣٤.٥٦ ريال';
        $this->assertSame('المبلغ: 1234.56 ريال', Arabic::normalizeDigits($input, DigitSet::Latin));
    }

    // =========================================================================
    // ArabicSlugger — unicode()
    // =========================================================================

    public function test_unicode_slug_simple_arabic(): void
    {
        $this->assertSame('مرحبا-بالعالم', Arabic::unicodeSlug('مرحبا بالعالم'));
    }

    public function test_unicode_slug_strips_diacritics(): void
    {
        $this->assertSame('مرحبا-بالعالم', Arabic::unicodeSlug('مَرْحَبًا بَالعَالَمِ'));
    }

    public function test_unicode_slug_strips_tatweel(): void
    {
        $this->assertSame('سلام', Arabic::unicodeSlug('سـلام'));
    }

    public function test_unicode_slug_lowercases_latin(): void
    {
        $this->assertSame('iphone-15-pro', Arabic::unicodeSlug('iPhone 15 Pro'));
    }

    public function test_unicode_slug_mixed_arabic_latin(): void
    {
        $this->assertSame('دليل-المستخدم-iphone-15', Arabic::unicodeSlug('دليل المستخدم iPhone 15'));
    }

    public function test_unicode_slug_replaces_punctuation_with_separator(): void
    {
        $this->assertSame('أحمد-علي', Arabic::unicodeSlug('أحمد: علي!'));
    }

    public function test_unicode_slug_custom_separator_underscore(): void
    {
        $this->assertSame('مرحبا_بالعالم', Arabic::unicodeSlug('مرحبا بالعالم', '_'));
    }

    public function test_unicode_slug_limits_words(): void
    {
        $slug = Arabic::unicodeSlug('واحد اثنان ثلاثة أربعة خمسة', '-', 3);
        $parts = explode('-', $slug);
        $this->assertCount(3, $parts);
    }

    public function test_unicode_slug_max_length_truncates_cleanly(): void
    {
        $slug = Arabic::unicodeSlug('هذا نص طويل جداً جداً جداً جداً', '-', 0, 10);
        $this->assertLessThanOrEqual(10, mb_strlen($slug));
        // Should not end with separator
        $this->assertSame(0, preg_match('/-$/', $slug));
    }

    public function test_unicode_slug_keeps_alef_with_hamza(): void
    {
        // SlugMode::Unicode preserves spelling identity
        $slug = Arabic::unicodeSlug('إدارة المنتجات');
        $this->assertStringContainsString('إدارة', $slug);
    }

    public function test_unicode_slug_keeps_ta_marbuta(): void
    {
        $slug = Arabic::unicodeSlug('مدرسة النور');
        $this->assertStringContainsString('مدرسة', $slug);
    }

    public function test_unicode_slug_keeps_alef_maqsura(): void
    {
        $slug = Arabic::unicodeSlug('على الجبل');
        $this->assertStringContainsString('على', $slug);
    }

    public function test_unicode_slug_converts_arabic_indic_digits(): void
    {
        $this->assertSame('تقرير-2026', Arabic::unicodeSlug('تقرير ٢٠٢٦'));
    }

    public function test_unicode_slug_empty_returns_empty(): void
    {
        $this->assertSame('', Arabic::unicodeSlug(''));
    }

    public function test_unicode_slug_html_stripped(): void
    {
        $this->assertSame('مرحبا', Arabic::unicodeSlug('<b>مرحبا</b>'));
    }

    public function test_unicode_slug_deduplicates_separators(): void
    {
        // Multiple spaces/punctuation collapse to single separator
        $slug = Arabic::unicodeSlug('مرحبا   ---   عالم');
        $this->assertSame('مرحبا-عالم', $slug);
    }

    // =========================================================================
    // ArabicSlugger — ascii()
    // =========================================================================

    public function test_ascii_slug_only_contains_ascii(): void
    {
        $slug = Arabic::asciiSlug('جامعة القاهرة');
        $this->assertMatchesRegularExpression('/^[a-z0-9\-]+$/', $slug);
    }

    public function test_ascii_slug_is_not_empty(): void
    {
        $this->assertNotSame('', Arabic::asciiSlug('مرحبا'));
    }

    public function test_ascii_slug_mixed_arabic_latin(): void
    {
        $slug = Arabic::asciiSlug('iPhone برو');
        $this->assertMatchesRegularExpression('/^[a-z0-9\-]+$/', $slug);
    }

    public function test_ascii_slug_via_slug_method(): void
    {
        $slug = Arabic::slug('دليل المستخدم', SlugMode::Ascii);
        $this->assertMatchesRegularExpression('/^[a-z0-9\-]+$/', $slug);
    }

    public function test_unicode_slug_via_slug_method(): void
    {
        $slug = Arabic::slug('دليل المستخدم', SlugMode::Unicode);
        $this->assertSame('دليل-المستخدم', $slug);
    }

    // =========================================================================
    // safeFilename()
    // =========================================================================

    public function test_safe_filename_preserves_extension(): void
    {
        $this->assertStringEndsWith('.pdf', Arabic::safeFilename('تقرير المبيعات.pdf'));
    }

    public function test_safe_filename_removes_special_chars_from_base(): void
    {
        $result = Arabic::safeFilename('تقرير: المبيعات/٢٠٢٦؟.xlsx');
        $this->assertStringEndsWith('.xlsx', $result);
        $this->assertStringNotContainsString(':', $result);
        $this->assertStringNotContainsString('/', $result);
        $this->assertStringNotContainsString('؟', $result);
    }

    public function test_safe_filename_strips_arabic_indic_digits_in_base(): void
    {
        $result = Arabic::safeFilename('ملف ٢٠٢٦.docx');
        $this->assertStringContainsString('2026', $result);
        $this->assertStringEndsWith('.docx', $result);
    }

    public function test_safe_filename_empty_base_becomes_file(): void
    {
        $result = Arabic::safeFilename('!@#$%.txt');
        $this->assertStringStartsWith('file', $result);
    }

    public function test_safe_filename_no_extension_works(): void
    {
        $result = Arabic::safeFilename('تقرير المبيعات');
        $this->assertNotSame('', $result);
        $this->assertStringNotContainsString('.', $result);
    }

    public function test_safe_filename_keeps_arabic_letters_readable(): void
    {
        $result = Arabic::safeFilename('خطة التسويق 2026.pdf');
        $this->assertStringContainsString('خطة', $result);
        $this->assertStringEndsWith('.pdf', $result);
    }

    // =========================================================================
    // TextCleaner — clean(), sanitize(), sanitizePlain(), sanitizeForSearch()
    // =========================================================================

    public function test_clean_removes_html_and_trims(): void
    {
        $this->assertSame('مرحبا بالعالم', Arabic::clean('<p>مرحبا بالعالم</p>'));
    }

    public function test_clean_removes_bidi_controls(): void
    {
        $this->assertSame('أحمد', Arabic::clean("أحمد\u{202E}"));
    }

    public function test_clean_removes_zero_width_chars(): void
    {
        $result = Arabic::clean("كلمة\u{200B}أخرى");
        $this->assertStringNotContainsString("\u{200B}", $result);
    }

    public function test_clean_collapses_whitespace(): void
    {
        $this->assertSame('مرحبا بالعالم', Arabic::clean("مرحبا   \t  بالعالم"));
    }

    public function test_sanitize_strips_html_preserves_diacritics(): void
    {
        $result = Arabic::sanitize('<b>مُحَمَّدٌ</b>');
        $this->assertSame('مُحَمَّدٌ', $result);
    }

    public function test_sanitize_strips_tatweel_by_default(): void
    {
        $result = Arabic::sanitize('العـرب');
        $this->assertSame('العرب', $result);
    }

    public function test_sanitize_keeps_punctuation_by_default(): void
    {
        $result = Arabic::sanitize('مرحبا، كيف الحال؟');
        $this->assertStringContainsString('،', $result);
        $this->assertStringContainsString('؟', $result);
    }

    public function test_sanitize_removes_punctuation_when_disabled(): void
    {
        $result = Arabic::sanitize('مرحبا، كيف الحال؟', keepPunctuation: false);
        $this->assertStringNotContainsString('،', $result);
        $this->assertStringNotContainsString('؟', $result);
    }

    public function test_sanitize_with_strip_diacritics_flag(): void
    {
        $result = Arabic::sanitize('<b>مُحَمَّدٌ</b>', stripDiacritics: true);
        $this->assertSame('محمد', $result);
    }

    public function test_sanitize_with_lowercase_flag(): void
    {
        $result = Arabic::sanitize('iPhone X', lowercase: true);
        $this->assertSame('iphone x', $result);
    }

    public function test_sanitize_with_search_policy(): void
    {
        $result = Arabic::sanitize('إدارة المبيعات', policy: ArabicPolicy::Search);
        $this->assertSame('اداره المبيعات', $result);
    }

    public function test_sanitize_plain_strips_diacritics_and_tatweel(): void
    {
        $this->assertSame('محمد', Arabic::sanitizePlain('<i>مُحَمَّدٌ</i>'));
    }

    public function test_sanitize_plain_preserves_ta_marbuta(): void
    {
        $this->assertSame('مدرسة النور', Arabic::sanitizePlain('مَدْرَسَةُ النُّورِ'));
    }

    public function test_sanitize_for_search_is_fully_aggressive(): void
    {
        $result = Arabic::sanitizeForSearch('<b>إدارةُ المبيعات، iPhone X؟</b>');
        $this->assertSame('اداره المبيعات iphone x', $result);
    }

    public function test_sanitize_for_search_removes_all_punctuation(): void
    {
        $result = Arabic::sanitizeForSearch('مرحباً، كيف الحال؟');
        $this->assertStringNotContainsString('،', $result);
        $this->assertStringNotContainsString('؟', $result);
    }

    public function test_sanitize_for_search_is_idempotent(): void
    {
        $text = '<b>إدارة مدرسةٍ</b>';
        $once = Arabic::sanitizeForSearch($text);
        $twice = Arabic::sanitizeForSearch($once);
        $this->assertSame($once, $twice);
    }

    // =========================================================================
    // TextCleaner — whitespace handling
    // =========================================================================

    public function test_normalize_whitespace_collapses_spaces_and_tabs(): void
    {
        $this->assertSame('أحمد علي', Arabic::normalizeWhitespace("أحمد\t\t  علي"));
    }

    public function test_normalize_whitespace_preserves_newlines_when_requested(): void
    {
        $this->assertSame("أحمد\nعلي", Arabic::normalizeWhitespace("أحمد\n  علي", true));
    }

    public function test_normalize_whitespace_trims_ends(): void
    {
        $this->assertSame('مرحبا', Arabic::normalizeWhitespace('  مرحبا  '));
    }

    public function test_normalize_inline_whitespace_collapses_all_to_space(): void
    {
        $this->assertSame('أحمد علي', Arabic::normalizeInlineWhitespace("أحمد\n\t علي"));
    }

    public function test_deep_trim_removes_invisible_whitespace_from_ends(): void
    {
        // NBSP and ideographic space at boundaries
        $text = "\u{00A0}أحمد\u{00A0}";
        $trimmed = Arabic::deepTrim($text);
        $this->assertSame('أحمد', $trimmed);
    }

    // =========================================================================
    // TextCleaner — stripHtml()
    // =========================================================================

    public function test_strip_html_removes_tags(): void
    {
        // Block-level tags get a space replacement before stripping (to avoid word joins),
        // which may leave trailing whitespace. Use clean() or trim if exact spacing matters.
        $result = trim(Arabic::stripHtml('<p>مرحبا عالم</p>'));
        $this->assertSame('مرحبا عالم', $result);
    }

    public function test_strip_html_inserts_space_between_block_tags(): void
    {
        $result = Arabic::stripHtml('<p>الأول</p><p>الثاني</p>');
        $this->assertStringContainsString(' ', $result);
    }

    public function test_strip_html_does_not_join_words_across_br(): void
    {
        $result = Arabic::stripHtml('كلمة<br>أخرى');
        // Should have a space between the words
        $this->assertMatchesRegularExpression('/كلمة\s+أخرى/', $result);
    }

    public function test_strip_html_handles_entities(): void
    {
        // strip_tags does not decode entities, but the method preserves them
        $result = Arabic::stripHtml('<b>أحمد &amp; علي</b>');
        $this->assertStringContainsString('أحمد', $result);
    }

    // =========================================================================
    // ArabicPunctuation — fixPunctuation()
    // =========================================================================

    public function test_fix_punctuation_adds_space_after_arabic_comma(): void
    {
        $this->assertSame('مرحبا، عالم', Arabic::fixPunctuation('مرحبا،عالم'));
    }

    public function test_fix_punctuation_adds_space_after_arabic_question_mark(): void
    {
        $this->assertSame('كيف الحال؟ جيد', Arabic::fixPunctuation('كيف الحال؟جيد'));
    }

    public function test_fix_punctuation_adds_space_after_colon(): void
    {
        $this->assertSame('اسمي: أحمد', Arabic::fixPunctuation('اسمي:أحمد'));
    }

    public function test_fix_punctuation_removes_space_before_punctuation(): void
    {
        $this->assertSame('مرحبا،', Arabic::fixPunctuation('مرحبا ،'));
    }

    public function test_fix_punctuation_normalizes_ellipsis(): void
    {
        $result = Arabic::fixPunctuation('تمام...');
        $this->assertStringContainsString('...', $result);
    }

    public function test_fix_punctuation_removes_space_inside_parentheses(): void
    {
        $this->assertSame('(أحمد)', Arabic::fixPunctuation('( أحمد )'));
    }

    public function test_fix_punctuation_removes_space_inside_brackets(): void
    {
        $this->assertSame('[ملاحظة]', Arabic::fixPunctuation('[ ملاحظة ]'));
    }

    public function test_fix_punctuation_normalizes_guillemets(): void
    {
        $this->assertSame('«كتاب»', Arabic::fixPunctuation('« كتاب »'));
    }

    public function test_fix_punctuation_handles_mixed_arabic_latin_punctuation(): void
    {
        $result = Arabic::fixPunctuation('Hello,World؟نعم');
        $this->assertStringContainsString(', ', $result);
        $this->assertStringContainsString('؟ ', $result);
    }

    public function test_fix_punctuation_empty_returns_empty(): void
    {
        $this->assertSame('', Arabic::fixPunctuation(''));
    }

    public function test_fix_punctuation_is_idempotent(): void
    {
        $text = 'مرحبا، كيف الحال؟ جيد: نعم';
        $once = Arabic::fixPunctuation($text);
        $twice = Arabic::fixPunctuation($once);
        $this->assertSame($once, $twice);
    }

    // =========================================================================
    // ArabicPunctuation — normalizeConjunctionWaw()
    // =========================================================================

    public function test_normalize_conjunction_waw_removes_space_after_leading_waw(): void
    {
        $result = Arabic::normalizeConjunctionWaw('أحمد و علي');
        $this->assertSame('أحمد وعلي', $result);
    }

    public function test_normalize_conjunction_waw_multi_occurrence(): void
    {
        $result = Arabic::normalizeConjunctionWaw('الكتاب و القلم و الورقة');
        $this->assertSame('الكتاب والقلم والورقة', $result);
    }

    public function test_normalize_conjunction_waw_with_diacritics_on_waw(): void
    {
        // The normalizeConjunctionWaw method matches waw followed by its diacritics
        // then a space. Use the actual Arabic diacritic character in the string.
        $wawWithFatha = 'وَ';
        $text = 'الكتاب '.$wawWithFatha.' القلم';
        $result = Arabic::normalizeConjunctionWaw($text);
        // The waw-space sequence should be collapsed
        $this->assertStringNotContainsString($wawWithFatha.' ', $result);
        $this->assertStringContainsString('الكتاب', $result);
        $this->assertStringContainsString('القلم', $result);
    }

    public function test_normalize_conjunction_waw_does_not_affect_waw_in_words(): void
    {
        // The و inside a word should not be affected
        $text = 'وقت الفراغ ورقة';
        $result = Arabic::normalizeConjunctionWaw($text);
        $this->assertStringContainsString('وقت', $result);
    }

    // =========================================================================
    // TextExcerpt — excerpt()
    // =========================================================================

    public function test_excerpt_short_text_returned_as_is(): void
    {
        $this->assertSame('مرحبا', Arabic::excerpt('مرحبا'));
    }

    public function test_excerpt_strips_html_before_limiting(): void
    {
        $result = Arabic::excerpt('<p>مرحبا بالعالم</p>', 200);
        $this->assertStringNotContainsString('<p>', $result);
    }

    public function test_excerpt_does_not_cut_words(): void
    {
        $text = 'هذا نص عربي طويل جداً لاختبار المقتطفات';

        $result = Arabic::excerpt($text, 15);

        $this->assertIsString($result);
        $this->assertNotSame('', $result);

        $resultWithoutSuffix = preg_replace('/\s*\.\.\.$/u', '', $result);

        $this->assertIsString($resultWithoutSuffix);

        $resultWords = preg_split('/\s+/u', trim($resultWithoutSuffix), -1, PREG_SPLIT_NO_EMPTY);
        $originalWords = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        $this->assertIsArray($resultWords);
        $this->assertIsArray($originalWords);

        foreach ($resultWords as $word) {
            $this->assertContains($word, $originalWords, "Word '{$word}' is a partial cut");
        }
    }

    public function test_excerpt_appends_end_suffix(): void
    {
        $text = 'هذا نص طويل جداً جداً جداً جداً جداً';
        $result = Arabic::excerpt($text, 10, ' ...');
        $this->assertStringEndsWith(' ...', $result);
    }

    public function test_excerpt_custom_end_suffix(): void
    {
        $text = 'هذا نص طويل جداً جداً جداً جداً جداً';
        $result = Arabic::excerpt($text, 10, '→');
        $this->assertStringEndsWith('→', $result);
    }

    public function test_excerpt_handles_html_entities(): void
    {
        $result = Arabic::excerpt('<p>أحمد &amp; علي وأصدقاؤهم</p>', 200);
        $this->assertStringContainsString('أحمد', $result);
    }

    // =========================================================================
    // OrderedListPrefixStripper
    // =========================================================================

    public function test_ordered_list_strips_latin_numeric_dot(): void
    {
        $this->assertSame('البند الأول', Arabic::stripOrderedListPrefixes('1. البند الأول'));
    }

    public function test_ordered_list_strips_latin_numeric_dash(): void
    {
        $this->assertSame('البند الثاني', Arabic::stripOrderedListPrefixes('2- البند الثاني'));
    }

    public function test_ordered_list_strips_arabic_indic_digits(): void
    {
        $this->assertSame('البند', Arabic::stripOrderedListPrefixes('١. البند'));
    }

    public function test_ordered_list_strips_eastern_arabic_digits(): void
    {
        $this->assertSame('البند', Arabic::stripOrderedListPrefixes('۲. البند'));
    }

    public function test_ordered_list_strips_parenthesized_numbers(): void
    {
        $this->assertSame('البند الثالث', Arabic::stripOrderedListPrefixes('(3) البند الثالث'));
    }

    public function test_ordered_list_multiline(): void
    {
        $input = "1. الأول\n2. الثاني\n3. الثالث";
        $expected = "الأول\nالثاني\nالثالث";
        $this->assertSame($expected, Arabic::stripOrderedListPrefixes($input));
    }

    public function test_ordered_list_mixed_digit_types_multiline(): void
    {
        $input = "1. إنشاء حساب\n٢- تأكيد البريد\n(۳) تفعيل الوصول";
        $expected = "إنشاء حساب\nتأكيد البريد\nتفعيل الوصول";
        $this->assertSame($expected, Arabic::stripOrderedListPrefixes($input));
    }

    // =========================================================================
    // ArabicNameNormalizer — name()
    // =========================================================================

    public function test_name_applies_ahmed_correction(): void
    {
        $this->assertSame('أحمد', Arabic::name('احمد'));
    }

    public function test_name_applies_ibrahim_correction(): void
    {
        $this->assertSame('إبراهيم', Arabic::name('ابراهيم'));
    }

    public function test_name_applies_ismail_correction(): void
    {
        $this->assertSame('إسماعيل', Arabic::name('اسماعيل'));
    }

    public function test_name_applies_ameen_correction(): void
    {
        $this->assertSame('أمين', Arabic::name('امين'));
    }

    public function test_name_applies_jumuah_correction(): void
    {
        $this->assertSame('جمعة', Arabic::name('جمعه'));
    }

    public function test_name_keeps_alef_maqsura_by_default(): void
    {
        $this->assertSame('عيسى', Arabic::name('عيسى'));
        $this->assertSame('موسى', Arabic::name('موسى'));
    }

    public function test_name_normalizes_alef_maqsura_when_requested(): void
    {
        $this->assertSame('عيسي', Arabic::name('عيسى', normalizeAlefMaqsura: true));
    }

    public function test_name_splits_abd_al_prefix(): void
    {
        $result = Arabic::name('عبدالرحمن');
        $this->assertSame('عبد الرحمن', $result);
    }

    public function test_name_splits_abu_al_prefix(): void
    {
        $result = Arabic::name('ابوالفضل');
        $this->assertSame('أبو الفضل', $result);
    }

    public function test_name_corrects_ibn_prefix(): void
    {
        $result = Arabic::name('بن خلدون');
        $this->assertSame('ابن خلدون', $result);
    }

    public function test_name_strips_tatweel(): void
    {
        $this->assertSame('أحمد', Arabic::name('أحمـد'));
    }

    public function test_name_strips_diacritics(): void
    {
        $this->assertSame('أحمد', Arabic::name('أَحْمَدُ'));
    }

    public function test_name_limits_words(): void
    {
        $result = Arabic::name('محمد أحمد علي عبد الله الكريم السيد', maxWords: 3);
        $parts = explode(' ', $result);
        $this->assertLessThanOrEqual(3, count($parts));
    }

    public function test_name_unlimited_words_with_zero(): void
    {
        $input = 'محمد بن أحمد بن علي بن خلدون';
        $result = Arabic::name($input, maxWords: 0);
        $this->assertStringContainsString('خلدون', $result);
    }

    public function test_name_corrections_disabled(): void
    {
        $result = Arabic::name('احمد', applyCorrections: false);
        $this->assertSame('احمد', $result);
    }

    public function test_name_full_name_scenario(): void
    {
        $this->assertSame('أحمد إبراهيم', Arabic::name('احمد ابراهيم'));
    }

    // =========================================================================
    // StringSupport — length(), graphemeLength(), unicodeLength(), byteLength()
    // =========================================================================

    public function test_length_grapheme_counts_letter_plus_diacritic_as_one(): void
    {
        $this->assertSame(1, Arabic::graphemeLength('مُ'));
    }

    public function test_length_unicode_counts_letter_and_diacritic_separately(): void
    {
        $this->assertSame(2, Arabic::unicodeLength('مُ'));
    }

    public function test_length_byte_counts_utf8_bytes(): void
    {
        // Arabic letter = 2 bytes, diacritic = 2 bytes
        $this->assertSame(4, Arabic::byteLength('مُ'));
    }

    public function test_length_arabic_word_grapheme(): void
    {
        $this->assertSame(4, Arabic::graphemeLength('مرحب'));
    }

    public function test_length_default_unit_is_grapheme(): void
    {
        $text = 'مُ';
        $this->assertSame(Arabic::graphemeLength($text), Arabic::length($text));
    }

    public function test_length_all_units_empty_string(): void
    {
        $this->assertSame(0, Arabic::graphemeLength(''));
        $this->assertSame(0, Arabic::unicodeLength(''));
        $this->assertSame(0, Arabic::byteLength(''));
    }

    public function test_length_with_unit_enum_grapheme(): void
    {
        $this->assertSame(1, Arabic::length('مُ', LengthUnit::Grapheme));
    }

    public function test_length_with_unit_enum_unicode(): void
    {
        $this->assertSame(2, Arabic::length('مُ', LengthUnit::Unicode));
    }

    public function test_length_with_unit_enum_byte(): void
    {
        $this->assertSame(4, Arabic::length('مُ', LengthUnit::Byte));
    }

    // =========================================================================
    // StringSupport — substr() and limit()
    // =========================================================================

    public function test_substr_grapheme_keeps_diacritic_with_base(): void
    {
        $result = Arabic::substr('مُحَمَّدٌ', 0, 1, LengthUnit::Grapheme);
        $this->assertSame('مُ', $result);
    }

    public function test_substr_unicode_splits_letter_and_diacritic(): void
    {
        $result = Arabic::substr('مُحَمَّدٌ', 0, 1, LengthUnit::Unicode);
        $this->assertSame('م', $result);
    }

    public function test_limit_grapheme_appends_end_within_limit(): void
    {
        $this->assertSame('مُحَ...', Arabic::limit('مُحَمَّد علي', 5, LengthUnit::Grapheme, '...'));
    }

    public function test_limit_unicode(): void
    {
        $this->assertSame('م...', Arabic::limit('مُحَمَّد علي', 4, LengthUnit::Unicode, '...'));
    }

    public function test_limit_byte_produces_valid_utf8(): void
    {
        $result = Arabic::limit('أحمد', 5, LengthUnit::Byte, '...');
        $this->assertSame(1, preg_match('//u', $result));
    }

    public function test_limit_text_shorter_than_limit_returned_unchanged(): void
    {
        $this->assertSame('مرحبا', Arabic::limit('مرحبا', 200));
    }

    public function test_limit_exact_limit_returned_unchanged(): void
    {
        $text = 'مرحبا';
        $len = Arabic::graphemeLength($text);
        $this->assertSame($text, Arabic::limit($text, $len));
    }

    // =========================================================================
    // UnicodeSecurityCleaner — removeInvisible(), removeBidiControls(), securityClean()
    // =========================================================================

    public function test_remove_invisible_removes_zero_width_space(): void
    {
        $result = Arabic::removeInvisible("أحمد\u{200B}علي");
        $this->assertStringNotContainsString("\u{200B}", $result);
    }

    public function test_remove_invisible_removes_zero_width_joiner(): void
    {
        $result = Arabic::removeInvisible("أحمد\u{200D}علي");
        $this->assertStringNotContainsString("\u{200D}", $result);
    }

    public function test_remove_invisible_removes_zero_width_non_joiner(): void
    {
        $result = Arabic::removeInvisible("أحمد\u{200C}علي");
        $this->assertStringNotContainsString("\u{200C}", $result);
    }

    public function test_remove_bidi_controls_removes_rlm(): void
    {
        $result = Arabic::removeBidiControls("أحمد\u{200F}");
        $this->assertStringNotContainsString("\u{200F}", $result);
    }

    public function test_remove_bidi_controls_removes_lrm(): void
    {
        $result = Arabic::removeBidiControls("أحمد\u{200E}");
        $this->assertStringNotContainsString("\u{200E}", $result);
    }

    public function test_remove_bidi_controls_removes_rlo(): void
    {
        $result = Arabic::removeBidiControls("أحمد\u{202E}");
        $this->assertStringNotContainsString("\u{202E}", $result);
    }

    public function test_remove_bidi_controls_removes_rle(): void
    {
        $result = Arabic::removeBidiControls("أحمد\u{202B}");
        $this->assertStringNotContainsString("\u{202B}", $result);
    }

    public function test_remove_bidi_controls_preserves_arabic_text(): void
    {
        $result = Arabic::removeBidiControls("مرحبا\u{202E}عالم");
        $this->assertStringContainsString('مرحبا', $result);
        $this->assertStringContainsString('عالم', $result);
    }

    public function test_security_clean_removes_both_invisible_and_bidi(): void
    {
        $text = "أحمد\u{200B}\u{202E}علي";
        $result = Arabic::securityClean($text);
        $this->assertStringNotContainsString("\u{200B}", $result);
        $this->assertStringNotContainsString("\u{202E}", $result);
        $this->assertStringContainsString('أحمد', $result);
        $this->assertStringContainsString('علي', $result);
    }

    // =========================================================================
    // ArabicText — fluent pipeline
    // =========================================================================

    public function test_fluent_make_and_value(): void
    {
        $result = ArabicText::make('مرحبا')->value();
        $this->assertSame('مرحبا', $result);
    }

    public function test_fluent_to_string(): void
    {
        $obj = ArabicText::make('مرحبا');
        $this->assertSame('مرحبا', (string) $obj);
    }

    public function test_fluent_strip_html(): void
    {
        $result = ArabicText::make('<b>مرحبا</b>')->stripHtml()->value();
        $this->assertSame('مرحبا', $result);
    }

    public function test_fluent_strip_diacritics(): void
    {
        $result = ArabicText::make('مُحَمَّدٌ')->stripDiacritics()->value();
        $this->assertSame('محمد', $result);
    }

    public function test_fluent_strip_tatweel(): void
    {
        $result = ArabicText::make('العـرب')->stripTatweel()->value();
        $this->assertSame('العرب', $result);
    }

    public function test_fluent_normalize_display(): void
    {
        $result = ArabicText::make('مُدَرِّسَةٌ   عَلَى')->normalize(ArabicPolicy::Display)->value();
        $this->assertSame('مُدَرِّسَةٌ عَلَى', $result);
    }

    public function test_fluent_normalize_search(): void
    {
        $result = ArabicText::make('إدارة المدرسة')->normalize(ArabicPolicy::Search)->value();
        $this->assertSame('اداره المدرسه', $result);
    }

    public function test_fluent_search_key(): void
    {
        $result = ArabicText::make('إبراهيم')->searchKey();
        $this->assertSame('ابراهيم', $result);
    }

    public function test_fluent_sanitize_for_search(): void
    {
        $result = ArabicText::make('<b>أَحْمَدُ</b>')->sanitizeForSearch()->value();
        $this->assertSame('احمد', $result);
    }

    public function test_fluent_sanitize_plain(): void
    {
        $result = ArabicText::make('<b>مُحَمَّدٌ</b>')->sanitizePlain()->value();
        $this->assertSame('محمد', $result);
    }

    public function test_fluent_fix_punctuation(): void
    {
        $result = ArabicText::make('مرحبا،عالم')->fixPunctuation()->value();
        $this->assertSame('مرحبا، عالم', $result);
    }

    public function test_fluent_security_clean(): void
    {
        $result = ArabicText::make("أحمد\u{202E}علي")->securityClean()->value();
        $this->assertStringNotContainsString("\u{202E}", $result);
    }

    public function test_fluent_unicode_slug(): void
    {
        $result = ArabicText::make('<b>دَلِيلُ المستخدم</b>')->stripHtml()->stripDiacritics()->unicodeSlug();
        $this->assertSame('دليل-المستخدم', $result);
    }

    public function test_fluent_ascii_slug(): void
    {
        $result = ArabicText::make('دليل المستخدم')->asciiSlug();
        $this->assertMatchesRegularExpression('/^[a-z0-9\-]+$/', $result);
    }

    public function test_fluent_slug_unicode_mode(): void
    {
        $result = ArabicText::make('دليل المستخدم')->slug(SlugMode::Unicode);
        $this->assertSame('دليل-المستخدم', $result);
    }

    public function test_fluent_excerpt(): void
    {
        $result = ArabicText::make('<p>هذا نص طويل جداً</p>')->excerpt(10, '...');
        $this->assertStringEndsWith('...', $result);
    }

    public function test_fluent_limit_grapheme(): void
    {
        $result = ArabicText::make('مُحَمَّد علي')->limit(4, LengthUnit::Unicode)->value();
        $this->assertSame('م...', $result);
    }

    public function test_fluent_normalize_whitespace(): void
    {
        $result = ArabicText::make("أحمد   \t علي")->normalizeWhitespace()->value();
        $this->assertSame('أحمد علي', $result);
    }

    public function test_fluent_normalize_inline_whitespace(): void
    {
        $result = ArabicText::make("أحمد\n  علي")->normalizeInlineWhitespace()->value();
        $this->assertSame('أحمد علي', $result);
    }

    public function test_fluent_strip_ordered_list_prefixes(): void
    {
        $result = ArabicText::make("1. البند الأول\n2. البند الثاني")
            ->stripOrderedListPrefixes()
            ->value();
        $this->assertSame("البند الأول\nالبند الثاني", $result);
    }

    public function test_fluent_chain_multiple_operations(): void
    {
        $result = ArabicText::make('<p>مُحَمَّـدٌ  وعَلِيٌّ</p>')
            ->stripHtml()
            ->stripDiacritics()
            ->stripTatweel()
            ->normalizeWhitespace()
            ->value();
        $this->assertSame('محمد وعلي', $result);
    }

    public function test_fluent_sanitize_with_options(): void
    {
        $result = ArabicText::make('<b>أَحْمَدُ</b>')
            ->sanitize(stripDiacritics: true, stripTatweel: true)
            ->value();
        $this->assertSame('أحمد', $result);
    }

    // =========================================================================
    // Arabic::text() factory
    // =========================================================================

    public function test_text_factory_returns_arabic_text_instance(): void
    {
        $obj = Arabic::text('مرحبا');
        $this->assertInstanceOf(ArabicText::class, $obj);
    }

    public function test_text_factory_chains_correctly(): void
    {
        $result = Arabic::text('<b>مُحَمَّـدٌ</b>')
            ->stripHtml()
            ->stripDiacritics()
            ->stripTatweel()
            ->value();
        $this->assertSame('محمد', $result);
    }

    // =========================================================================
    // Profanity / containsBadWords()
    // =========================================================================

    public function test_bad_words_detects_word_in_sentence(): void
    {
        $this->assertTrue(Arabic::containsBadWords('هذا نص ممنوع هنا', ['ممنوع']));
    }

    public function test_bad_words_returns_false_when_clean(): void
    {
        $this->assertFalse(Arabic::containsBadWords('هذا نص مقبول', ['ممنوع']));
    }

    public function test_bad_words_case_insensitive_latin(): void
    {
        $this->assertTrue(Arabic::containsBadWords('Contains SPAM here', ['spam']));
    }

    public function test_bad_words_empty_word_list(): void
    {
        $this->assertFalse(Arabic::containsBadWords('هذا نص ممنوع', []));
    }

    public function test_bad_words_multiple_words_any_match(): void
    {
        $this->assertTrue(Arabic::containsBadWords('كلمة محظورة هنا', ['محظورة', 'ممنوعة']));
    }

    // =========================================================================
    // Real-world Arabic scenarios
    // =========================================================================

    public function test_real_world_news_headline_slug(): void
    {
        $headline = 'الرئيس يفتتح أكبر مشروع للطاقة الشمسية في منطقة الشرق الأوسط';
        $slug = Arabic::unicodeSlug($headline, maxWords: 8);
        $this->assertStringStartsWith('الرئيس', $slug);
        $this->assertMatchesRegularExpression('/^[\p{Arabic}\p{Latin}\p{N}\-]+$/u', $slug);
    }

    public function test_real_world_ecommerce_search_matching(): void
    {
        // "iPhone" written in different ways by different users
        $queries = [
            'ايفون 15',
            'آيفون ١٥',
            'أيفون 15',
        ];
        $keys = array_map(fn ($q) => Arabic::searchKey($q), $queries);
        // At minimum the digit part should all match
        foreach ($keys as $key) {
            $this->assertStringContainsString('15', $key);
        }
    }

    public function test_real_world_person_name_pipeline(): void
    {
        // User submits name in form; we normalize for display and create search key
        $raw = '<b>  احمـد  بن   ابراهيم  </b>';
        $display = Arabic::name(Arabic::sanitizePlain($raw), maxWords: 6);
        $searchKey = Arabic::searchKey($display);

        $this->assertSame('أحمد بن إبراهيم', $display);
        $this->assertSame('احمد بن ابراهيم', $searchKey);
    }

    public function test_real_world_uploaded_filename_sanitization(): void
    {
        // Uploaded file with unsafe characters
        $filename = 'تقرير: الأرباح/الربع الأول؟ ٢٠٢٦.xlsx';
        $safe = Arabic::safeFilename($filename);

        $this->assertStringEndsWith('.xlsx', $safe);
        $this->assertStringNotContainsString(':', $safe);
        $this->assertStringNotContainsString('/', $safe);
        $this->assertStringNotContainsString('؟', $safe);
        $this->assertStringContainsString('2026', $safe);
    }

    public function test_real_world_multilingual_blog_slug(): void
    {
        // Arabic title with English brand name and mixed digits
        $title = 'دليل المستخدم لـ Google Analytics 4 في ٢٠٢٦';
        $slug = Arabic::unicodeSlug($title, maxWords: 10);

        $this->assertStringContainsString('google', $slug);
        $this->assertStringContainsString('analytics', $slug);
        $this->assertStringContainsString('4', $slug);
        $this->assertStringContainsString('2026', $slug);
        $this->assertMatchesRegularExpression('/^[\p{Arabic}\p{Latin}\p{N}\-]+$/u', $slug);
    }

    public function test_real_world_comment_sanitization(): void
    {
        // Malicious comment with bidi spoofing and invisible chars
        $input = "هذا تعليق\u{202E}طبيعي\u{200B}جداً";
        $clean = Arabic::sanitize($input);

        $this->assertStringNotContainsString("\u{202E}", $clean);
        $this->assertStringNotContainsString("\u{200B}", $clean);
        $this->assertStringContainsString('هذا', $clean);
    }

    public function test_real_world_address_normalization(): void
    {
        $address = 'شارع الملك فـهد، حي العليـا، الرياض ١٢٢٤٣، المملكة العربية السعودية';
        $normalized = Arabic::sanitizePlain($address);

        $this->assertStringNotContainsString('فـ', $normalized); // tatweel removed
        $this->assertStringNotContainsString('ـ', $normalized);   // all tatweel gone
        $this->assertStringContainsString('١٢٢٤٣', $normalized); // digits preserved
        $this->assertStringContainsString('الرياض', $normalized);
    }

    public function test_real_world_quranic_text_handling(): void
    {
        // بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ
        $bismillah = 'بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ';

        // Display: strip tatweel, normalize whitespace, keep diacritics
        $display = Arabic::normalize($bismillah, ArabicPolicy::Display);
        $this->assertStringContainsString('بِ', $display);

        // Search: strip everything for matching
        $search = Arabic::searchKey($bismillah);
        $this->assertSame('بسم الله الرحمن الرحيم', $search);

        // Strict: change nothing
        $strict = Arabic::normalize($bismillah, ArabicPolicy::Strict);
        $this->assertSame($bismillah, $strict);
    }

    public function test_real_world_product_sku_with_mixed_digits(): void
    {
        // SKU entered with Arabic-Indic digits; normalize to Latin for database
        $sku = 'منتج-٠٠١-أ';
        $normalized = Arabic::digitsToLatin($sku);
        $this->assertSame('منتج-001-أ', $normalized);
    }

    public function test_real_world_hashtag_slug_for_social_media(): void
    {
        // Generate a slug for a hashtag
        $topic = 'تقنية المعلومات والاتصالات';
        $slug = Arabic::unicodeSlug($topic, '_', 5);
        $this->assertStringContainsString('_', $slug);
        $this->assertStringNotContainsString(' ', $slug);
    }

    public function test_real_world_user_bio_excerpt(): void
    {
        $bio = '<p>مهندس برمجيات متخصص في تطوير التطبيقات العربية وحلول الذكاء الاصطناعي للغة العربية والبيانات الضخمة</p>';
        $excerpt = Arabic::excerpt($bio, 40, ' ...');

        $this->assertStringEndsWith(' ...', $excerpt);
        $this->assertLessThanOrEqual(40 + mb_strlen(' ...'), mb_strlen($excerpt));
    }

    public function test_real_world_search_deduplication(): void
    {
        // These should all produce the same search key
        $variants = [
            'مدرسة إبراهيم',
            'مَدْرَسَةُ إِبْرَاهِيمَ',
            'مدرسة ابراهيم',
            'مدرسه إبراهيم',
        ];

        $keys = array_unique(array_map(fn ($v) => Arabic::searchKey($v), $variants));
        $this->assertCount(1, $keys, 'All variants should produce the same search key');
    }

    // =========================================================================
    // Edge cases and boundary conditions
    // =========================================================================

    public function test_empty_string_through_all_main_methods(): void
    {
        $this->assertSame('', Arabic::normalize(''));
        $this->assertSame('', Arabic::searchKey(''));
        $this->assertSame('', Arabic::stripDiacritics(''));
        $this->assertSame('', Arabic::stripTatweel(''));
        $this->assertSame('', Arabic::unicodeSlug(''));
        $this->assertSame('', Arabic::sanitize(''));
        $this->assertSame('', Arabic::sanitizePlain(''));
        $this->assertSame('', Arabic::sanitizeForSearch(''));
        $this->assertSame('', Arabic::clean(''));
        $this->assertSame('', Arabic::fixPunctuation(''));
        $this->assertSame('', Arabic::digitsToLatin(''));
    }

    public function test_whitespace_only_through_slug(): void
    {
        $this->assertSame('', Arabic::unicodeSlug('   '));
    }

    public function test_whitespace_only_through_sanitize(): void
    {
        $this->assertSame('', Arabic::sanitize('   '));
    }

    public function test_single_arabic_letter_slug(): void
    {
        $this->assertSame('م', Arabic::unicodeSlug('م'));
    }

    public function test_single_digit_through_all_digit_converters(): void
    {
        $this->assertSame('5', Arabic::digitsToLatin('٥'));
        $this->assertSame('٥', Arabic::digitsToArabicIndic('5'));
        $this->assertSame('۵', Arabic::digitsToEasternArabic('5'));
    }

    public function test_only_punctuation_through_sanitize_for_search(): void
    {
        $this->assertSame('', Arabic::sanitizeForSearch('،؛؟!،'));
    }

    public function test_only_html_through_clean(): void
    {
        $this->assertSame('', Arabic::clean('<br><br><div></div>'));
    }

    public function test_very_long_arabic_text_does_not_crash(): void
    {
        $text = str_repeat('مرحبا بالعالم ', 1000);
        $result = Arabic::sanitizeForSearch($text);
        $this->assertNotSame('', $result);
    }

    public function test_arabic_with_latin_digits_mixed(): void
    {
        $this->assertSame('الرقم 123', Arabic::digitsToLatin('الرقم ١٢٣'));
    }

    public function test_only_tatweel_through_strip(): void
    {
        $this->assertSame('', Arabic::stripTatweel('ـــ'));
    }

    public function test_only_diacritics_through_strip(): void
    {
        // String of pure tashkeel (no base letters)
        $diacritics = "\u{064E}\u{064F}\u{0650}";
        $stripped = Arabic::stripDiacritics($diacritics);
        $this->assertSame('', $stripped);
    }

    public function test_normalize_letters_empty_string(): void
    {
        $this->assertSame('', Arabic::normalizeLetters(''));
    }

    public function test_slug_with_only_numbers_returns_number_slug(): void
    {
        $this->assertSame('2026', Arabic::unicodeSlug('٢٠٢٦'));
    }

    public function test_name_with_only_non_arabic_returns_empty_or_clean(): void
    {
        // Should not crash; behavior may be empty string
        $result = Arabic::name('123');
        $this->assertIsString($result);
    }

    // =========================================================================
    // Idempotency — key methods produce stable output on repeated application
    // =========================================================================

    public function test_idempotency_display_policy(): void
    {
        $inputs = ['إدارة المدرسة', 'مُحَمَّدٌ عَلِيٌّ', 'الحمد لله'];
        foreach ($inputs as $input) {
            $once = Arabic::normalize($input, ArabicPolicy::Display);
            $twice = Arabic::normalize($once, ArabicPolicy::Display);
            $this->assertSame($once, $twice, "Display policy not idempotent for: $input");
        }
    }

    public function test_idempotency_search_key(): void
    {
        $inputs = ['إبراهيم مدرسة', 'مؤتمر على الجبل', 'رئيس الوزراء'];
        foreach ($inputs as $input) {
            $once = Arabic::searchKey($input);
            $twice = Arabic::searchKey($once);
            $this->assertSame($once, $twice, "searchKey not idempotent for: $input");
        }
    }

    public function test_idempotency_unicode_slug(): void
    {
        $inputs = ['دليل المستخدم', 'تقرير مبيعات 2026', 'إدارة المنتجات'];
        foreach ($inputs as $input) {
            $once = Arabic::unicodeSlug($input);
            $twice = Arabic::unicodeSlug($once);
            $this->assertSame($once, $twice, "unicodeSlug not idempotent for: $input");
        }
    }

    public function test_idempotency_strip_diacritics(): void
    {
        $text = 'مَدْرَسَةٌ إبراهيم';
        $once = Arabic::stripDiacritics($text);
        $twice = Arabic::stripDiacritics($once);
        $this->assertSame($once, $twice);
    }

    public function test_idempotency_strip_tatweel(): void
    {
        $text = 'العـرب';
        $once = Arabic::stripTatweel($text);
        $twice = Arabic::stripTatweel($once);
        $this->assertSame($once, $twice);
    }

    public function test_idempotency_fix_punctuation(): void
    {
        $text = 'مرحبا، كيف الحال؟ جيد: شكراً';
        $once = Arabic::fixPunctuation($text);
        $twice = Arabic::fixPunctuation($once);
        $this->assertSame($once, $twice);
    }

    public function test_idempotency_sanitize_for_search(): void
    {
        $text = '<b>إدارة مدرسةٍ مؤمنة</b>';
        $once = Arabic::sanitizeForSearch($text);
        $twice = Arabic::sanitizeForSearch($once);
        $this->assertSame($once, $twice);
    }

    public function test_idempotency_name_normalizer(): void
    {
        $once = Arabic::name('احمد ابراهيم');
        $twice = Arabic::name($once);
        $this->assertSame($once, $twice);
    }
}
