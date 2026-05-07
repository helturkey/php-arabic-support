<?php

declare(strict_types=1);

namespace ArabicSupport\Tests\Unit;

use ArabicSupport\Arabic;
use ArabicSupport\Enums\ArabicPolicy;
use ArabicSupport\Enums\DigitSet;
use ArabicSupport\Enums\HamzaPolicy;
use ArabicSupport\Enums\LengthUnit;
use ArabicSupport\Enums\SlugMode;
use ArabicSupport\Enums\TaMarbutaPolicy;
use ArabicSupport\Normalization\ArabicNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * Comprehensive test suite for the Arabic Support library.
 *
 * Coverage areas:
 *   - ArabicPolicy: Strict, Display, Search, Slug, Security
 *   - HamzaPolicy: Keep, Fold, FoldAll
 *   - TaMarbutaPolicy: Keep, Haa, Taa
 *   - normalizeLetters() with all flag combinations
 *   - searchKey() / normalize() edge cases
 *   - Slug generation (Unicode + ASCII) with Arabic real-world examples
 *   - Digit conversion (Arabic-Indic ↔ Eastern Arabic ↔ Latin)
 *   - Diacritics and tatweel stripping
 *   - safeFilename(), excerpt(), name()
 *   - length(), graphemeLength(), unicodeLength(), byteLength()
 *   - substr() and limit()
 *   - containsArabic(), isArabic(), arabicRatio(), inspect()
 *   - fixPunctuation(), normalizeConjunctionWaw()
 *   - stripOrderedListPrefixes()
 *   - clean(), sanitize(), sanitizePlain(), sanitizeForSearch()
 *   - normalizeWhitespace(), normalizeInlineWhitespace(), deepTrim(), stripHtml()
 *   - removeInvisible(), removeBidiControls(), securityClean()
 *   - Mixed Arabic–Latin–digit texts
 *   - Empty strings, whitespace-only, single characters
 *   - Idempotency (normalizing already-normalized text)
 */
final class ArabicNormalizerTest extends TestCase
{
    // =========================================================================
    // Display policy
    // =========================================================================

    public function test_display_keeps_diacritics(): void
    {
        $this->assertSame('مُدَرِّسَةٌ عَلَى', Arabic::normalize('مُدَرِّسَةٌ   عَلَى', ArabicPolicy::Display));
    }

    public function test_display_keeps_ta_marbuta(): void
    {
        $this->assertSame('مدرسة', Arabic::normalize('مدرسة', ArabicPolicy::Display));
    }

    public function test_display_keeps_hamza_above_waw(): void
    {
        $this->assertSame('مؤمن', Arabic::normalize('مؤمن', ArabicPolicy::Display));
    }

    public function test_display_keeps_hamza_above_ya(): void
    {
        $this->assertSame('رئيس', Arabic::normalize('رئيس', ArabicPolicy::Display));
    }

    public function test_display_keeps_alef_with_hamza_above(): void
    {
        $this->assertSame('أحمد', Arabic::normalize('أحمد', ArabicPolicy::Display));
    }

    public function test_display_keeps_alef_with_hamza_below(): void
    {
        $this->assertSame('إيمان', Arabic::normalize('إيمان', ArabicPolicy::Display));
    }

    public function test_display_keeps_alef_with_madda(): void
    {
        $this->assertSame('آثار', Arabic::normalize('آثار', ArabicPolicy::Display));
    }

    public function test_display_keeps_alef_maqsura(): void
    {
        $this->assertSame('على', Arabic::normalize('على', ArabicPolicy::Display));
    }

    public function test_display_collapses_multiple_spaces(): void
    {
        $this->assertSame('كلمة واحدة', Arabic::normalize('كلمة   واحدة', ArabicPolicy::Display));
    }

    public function test_display_collapses_tabs_and_spaces(): void
    {
        $this->assertSame('كلمة واحدة', Arabic::normalize("كلمة\t\t واحدة", ArabicPolicy::Display));
    }

    public function test_display_strips_tatweel(): void
    {
        $this->assertSame('العرب', Arabic::normalize('العـــرب', ArabicPolicy::Display));
    }

    public function test_display_strips_multiple_tatweel_runs(): void
    {
        // Display strips tatweel (ـ) but keeps diacritics like ً (tanwin fath).
        // Input: جميـــل (tatweel run) + جدًاـ (tanwin kept, trailing tatweel stripped)
        $this->assertSame('جميل جدًا', Arabic::normalize('جميـــل جدًاـ', ArabicPolicy::Display));
    }

    public function test_display_trims_leading_trailing_spaces(): void
    {
        $this->assertSame('مرحبا', Arabic::normalize('   مرحبا   ', ArabicPolicy::Display));
    }

    public function test_display_full_sentence_with_diacritics(): void
    {
        $input = 'بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ';
        $expected = 'بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ';
        $this->assertSame($expected, Arabic::normalize($input, ArabicPolicy::Display));
    }

    public function test_display_is_idempotent(): void
    {
        $text = 'مُدَرِّسَةٌ على';
        $once = Arabic::normalize($text, ArabicPolicy::Display);
        $this->assertSame($once, Arabic::normalize($once, ArabicPolicy::Display));
    }

    public function test_display_preserves_numbers(): void
    {
        $this->assertSame('رقم 42', Arabic::normalize('رقم  42', ArabicPolicy::Display));
    }

    // =========================================================================
    // Strict policy
    // =========================================================================

    public function test_strict_keeps_multiple_spaces(): void
    {
        $input = 'مُدَرِّسَةٌ   عَلَى';
        $this->assertSame($input, Arabic::normalize($input, ArabicPolicy::Strict));
    }

    public function test_strict_keeps_tatweel(): void
    {
        $this->assertSame('العـــرب', Arabic::normalize('العـــرب', ArabicPolicy::Strict));
    }

    public function test_strict_keeps_all_diacritics(): void
    {
        $input = 'بِسْمِ اللَّهِ';
        $this->assertSame($input, Arabic::normalize($input, ArabicPolicy::Strict));
    }

    public function test_strict_keeps_hamza_variants_untouched(): void
    {
        foreach (['أ', 'إ', 'آ', 'ؤ', 'ئ'] as $letter) {
            $this->assertSame($letter, Arabic::normalize($letter, ArabicPolicy::Strict));
        }
    }

    public function test_strict_keeps_ta_marbuta(): void
    {
        $this->assertSame('مدرسة', Arabic::normalize('مدرسة', ArabicPolicy::Strict));
    }

    // =========================================================================
    // Search policy
    // =========================================================================

    public function test_search_strips_all_diacritics(): void
    {
        $this->assertSame('احمد علي', Arabic::searchKey('أَحْمَدُ عَلِي'));
    }

    public function test_search_strips_shadda_and_sukun(): void
    {
        $this->assertSame('مدرس', Arabic::searchKey('مُدَرِّسٌ'));
    }

    public function test_search_folds_alef_with_hamza_above(): void
    {
        $this->assertSame('احمد', Arabic::searchKey('أحمد'));
    }

    public function test_search_folds_alef_with_hamza_below(): void
    {
        $this->assertSame('ايمان', Arabic::searchKey('إيمان'));
    }

    public function test_search_folds_alef_with_madda(): void
    {
        $this->assertSame('اثار', Arabic::searchKey('آثار'));
    }

    public function test_search_folds_alef_wasla(): void
    {
        $this->assertSame('ا', Arabic::searchKey("\u{0671}"));
    }

    public function test_search_folds_ta_marbuta_to_haa(): void
    {
        $this->assertSame('مدرسه', Arabic::searchKey('مدرسة'));
    }

    public function test_search_folds_ta_marbuta_mid_sentence(): void
    {
        $result = Arabic::searchKey('مدرسة النور');
        $this->assertSame('مدرسه النور', $result);
    }

    public function test_search_folds_alef_maqsura_to_ya(): void
    {
        $this->assertSame('علي', Arabic::searchKey('على'));
    }

    public function test_search_folds_waw_with_hamza(): void
    {
        $this->assertSame('مومن', Arabic::searchKey('مؤمن'));
    }

    public function test_search_folds_waw_with_hamza_mid_word(): void
    {
        $this->assertSame('سوال', Arabic::searchKey('سؤال'));
        $this->assertSame('رووس', Arabic::searchKey('رؤوس'));
    }

    public function test_search_does_no_t_fold_ya_with_hamza(): void
    {
        $result = Arabic::searchKey('رئيس');
        $this->assertStringContainsString('ئ', $result, 'ئ must be preserved in Search policy');
    }

    public function test_search_does_no_t_fold_ya_with_hamza_in_plural(): void
    {
        $result = Arabic::searchKey('مسائل');
        $this->assertStringContainsString('ئ', $result, 'ئ in مسائل must be preserved');
    }

    public function test_search_normalizes_persian_kaf(): void
    {
        $this->assertSame('كتاب', Arabic::searchKey('کتاب'));
    }

    public function test_search_normalizes_persian_ya(): void
    {
        $this->assertSame('يد', Arabic::searchKey('ید'));
    }

    public function test_search_converts_arabic_indic_digits_to_latin(): void
    {
        $this->assertSame('1234', Arabic::searchKey('١٢٣٤'));
    }

    public function test_search_converts_eastern_arabic_digits_to_latin(): void
    {
        $this->assertSame('5678', Arabic::searchKey('۵۶۷۸'));
    }

    public function test_search_lowercases_latin_letters(): void
    {
        $this->assertSame('hello world', Arabic::searchKey('Hello World'));
    }

    public function test_search_collapses_whitespace(): void
    {
        $this->assertSame('كلمه واحده', Arabic::searchKey('كلمة   واحدة'));
    }

    public function test_search_strips_tatweel(): void
    {
        $this->assertSame('العرب', Arabic::searchKey('العـــرب'));
    }

    public function test_search_full_sentence_normalization(): void
    {
        $input = 'أَحْمَدُ بنُ مُحَمَّدٍ يدرسُ في الجامعةِ';
        $expected = 'احمد بن محمد يدرس في الجامعه';
        $this->assertSame($expected, Arabic::searchKey($input));
    }

    public function test_search_is_idempotent(): void
    {
        $text = 'أَحْمَدُ عَلِي';
        $once = Arabic::searchKey($text);
        $twice = Arabic::searchKey($once);
        $this->assertSame($once, $twice, 'searchKey() must be idempotent');
    }

    public function test_search_handles_mixed_arabic_and_english(): void
    {
        $result = Arabic::searchKey('Hello مرحبا World');
        $this->assertSame('hello مرحبا world', $result);
    }

    // =========================================================================
    // Slug policy
    // =========================================================================

    public function test_slug_policy_strips_diacritics_but_preserves_alef_with_hamza_below(): void
    {
        $result = Arabic::normalize('الإدارة', ArabicPolicy::Slug);
        $this->assertStringContainsString('إ', $result);
    }

    public function test_slug_policy_strips_diacritics_preserving_spelling(): void
    {
        $result = Arabic::normalize('قائمةٌ تَجريبية على مَنصة الإدارة', ArabicPolicy::Slug);
        $this->assertSame('قائمة تجريبية على منصة الإدارة', $result);
    }

    public function test_slug_policy_does_not_fold_ta_marbuta(): void
    {
        $this->assertStringContainsString('ة', Arabic::normalize('مدرسة', ArabicPolicy::Slug));
    }

    public function test_slug_policy_does_not_fold_alef_maqsura(): void
    {
        $this->assertStringContainsString('ى', Arabic::normalize('على', ArabicPolicy::Slug));
    }

    public function test_slug_policy_does_not_fold_ya_with_hamza(): void
    {
        $normalizer = new ArabicNormalizer;

        $result = $normalizer->normalize(
            'قائِمةٌ رَئِيسيّة على مَنْصّةِ الإدارة',
            ArabicPolicy::Slug
        );

        $this->assertStringContainsString('ئ', $result);
        $this->assertStringContainsString('رئيسية', $result);
        $this->assertStringNotContainsString('رييسية', $result);
    }

    public function test_slug_policy_does_not_fold_waw_with_hamza(): void
    {
        $this->assertStringContainsString('ؤ', Arabic::normalize('سؤال', ArabicPolicy::Slug));
    }

    public function test_slug_policy_does_not_fold_alef_with_hamza_above(): void
    {
        $this->assertStringContainsString('أ', Arabic::normalize('أحمد', ArabicPolicy::Slug));
    }

    public function test_slug_policy_normalizes_persian_kaf(): void
    {
        $result = Arabic::normalize('کتاب', ArabicPolicy::Slug);
        $this->assertStringContainsString('ك', $result);
        $this->assertStringNotContainsString('ک', $result);
    }

    public function test_slug_policy_is_distinct_from_search_for_ta_marbuta(): void
    {
        $slug = Arabic::normalize('مدرِّسَةٌ', ArabicPolicy::Slug);
        $search = Arabic::normalize('مدرِّسَةٌ', ArabicPolicy::Search);
        $this->assertNotSame($slug, $search);
        $this->assertStringContainsString('ة', $slug);
        $this->assertStringContainsString('ه', $search);
    }

    public function test_slug_policy_lowercases_latin(): void
    {
        $result = Arabic::normalize('Hello عالم', ArabicPolicy::Slug);
        $this->assertSame('hello عالم', $result);
    }

    public function test_slug_policy_converts_arabic_indic_digits(): void
    {
        $result = Arabic::normalize('١٢٣', ArabicPolicy::Slug);
        $this->assertSame('123', $result);
    }

    // =========================================================================
    // Security policy
    // =========================================================================

    public function test_security_removes_zero_width_non_joiner(): void
    {
        $text = "مر\u{200C}حبا";
        $result = Arabic::normalize($text, ArabicPolicy::Security);
        $this->assertStringNotContainsString("\u{200C}", $result);
    }

    public function test_security_removes_zero_width_joiner(): void
    {
        $text = "مر\u{200D}حبا";
        $result = Arabic::normalize($text, ArabicPolicy::Security);
        $this->assertStringNotContainsString("\u{200D}", $result);
    }

    public function test_security_removes_lre_pdf_bidi_controls(): void
    {
        $text = "\u{202A}مرحبا\u{202C}";
        $result = Arabic::normalize($text, ArabicPolicy::Security);
        $this->assertStringNotContainsString("\u{202A}", $result);
        $this->assertStringNotContainsString("\u{202C}", $result);
    }

    public function test_security_still_strips_tatweel(): void
    {
        $this->assertSame('العرب', Arabic::normalize('العـــرب', ArabicPolicy::Security));
    }

    public function test_security_still_collapses_whitespace(): void
    {
        $this->assertSame('كلمة واحدة', Arabic::normalize('كلمة   واحدة', ArabicPolicy::Security));
    }

    public function test_security_keeps_diacritics_like_display(): void
    {
        $input = 'مُحَمَّدٌ';
        $this->assertSame(
            Arabic::normalize($input, ArabicPolicy::Display),
            Arabic::normalize($input, ArabicPolicy::Security),
        );
    }

    // =========================================================================
    // normalizeLetters() — explicit flag control
    // =========================================================================

    public function test_normalize_letters_hamza_keep_leaves_all_variants(): void
    {
        $normalizer = new ArabicNormalizer;
        $result = $normalizer->normalizeLetters(
            'أإآؤئ',
            hamza: HamzaPolicy::Keep,
            normalizeAlef: false,
            normalizeAlefMaqsura: false,
            normalizePersianLetters: false,
        );
        $this->assertSame('أإآؤئ', $result);
    }

    public function test_normalize_letters_hamza_fold_folds_waw_only(): void
    {
        $normalizer = new ArabicNormalizer;
        $result = $normalizer->normalizeLetters(
            'مؤمن رئيس',
            hamza: HamzaPolicy::Fold,
            normalizeAlef: false,
            normalizeAlefMaqsura: false,
            normalizePersianLetters: false,
        );
        $this->assertSame('مومن رئيس', $result);
        $this->assertStringContainsString('ئ', $result);
    }

    public function test_normalize_letters_hamza_fold_all_folds_both(): void
    {
        $normalizer = new ArabicNormalizer;
        $result = $normalizer->normalizeLetters('رئيس مؤمن', hamza: HamzaPolicy::FoldAll);
        $this->assertSame('رييس مومن', $result);
    }

    public function test_normalize_letters_ta_marbuta_haa(): void
    {
        $normalizer = new ArabicNormalizer;
        $result = $normalizer->normalizeLetters(
            'مدرسة',
            taMarbuta: TaMarbutaPolicy::Haa,
            normalizeAlef: false,
            normalizeAlefMaqsura: false,
            normalizePersianLetters: false,
        );
        $this->assertSame('مدرسه', $result);
    }

    public function test_normalize_letters_ta_marbuta_taa(): void
    {
        $normalizer = new ArabicNormalizer;
        $result = $normalizer->normalizeLetters(
            'مدرسة',
            taMarbuta: TaMarbutaPolicy::Taa,
            normalizeAlef: false,
            normalizeAlefMaqsura: false,
            normalizePersianLetters: false,
        );
        $this->assertSame('مدرست', $result);
    }

    public function test_normalize_letters_ta_marbuta_keep(): void
    {
        $normalizer = new ArabicNormalizer;
        $result = $normalizer->normalizeLetters('مدرسة', taMarbuta: TaMarbutaPolicy::Keep);
        $this->assertSame('مدرسة', $result);
    }

    public function test_normalize_letters_alef_normalization(): void
    {
        $normalizer = new ArabicNormalizer;
        $result = $normalizer->normalizeLetters(
            'أإآٱ',
            normalizeAlef: true,
            normalizeAlefMaqsura: false,
            normalizePersianLetters: false,
        );
        $this->assertSame('اااا', $result);
    }

    public function test_normalize_letters_alef_maqsura_normalization(): void
    {
        $normalizer = new ArabicNormalizer;
        $result = $normalizer->normalizeLetters(
            'على مستوى',
            normalizeAlef: false,
            normalizeAlefMaqsura: true,
            normalizePersianLetters: false,
        );
        $this->assertSame('علي مستوي', $result);
    }

    public function test_normalize_letters_persian_kaf_to_arabic_kaf(): void
    {
        $normalizer = new ArabicNormalizer;
        $result = $normalizer->normalizeLetters(
            'کتاب',
            normalizeAlef: false,
            normalizeAlefMaqsura: false,
            normalizePersianLetters: true,
        );
        $this->assertSame('كتاب', $result);
    }

    public function test_normalize_letters_persian_ya_to_arabic_ya(): void
    {
        $normalizer = new ArabicNormalizer;
        $result = $normalizer->normalizeLetters(
            'یوم',
            normalizeAlef: false,
            normalizeAlefMaqsura: false,
            normalizePersianLetters: true,
        );
        $this->assertSame('يوم', $result);
    }

    public function test_normalize_letters_persian_he_with_hamza_to_ta_marbuta(): void
    {
        $normalizer = new ArabicNormalizer;
        // ۀ (U+06C0) → ة
        $result = $normalizer->normalizeLetters(
            "خان\u{06C0}",
            normalizeAlef: false,
            normalizeAlefMaqsura: false,
            normalizePersianLetters: true,
        );
        $this->assertSame('خانة', $result);
    }

    public function test_normalize_letters_all_flags_disabled_returns_unchanged(): void
    {
        $normalizer = new ArabicNormalizer;
        $text = 'أإآ ؤئ ى ة';
        $result = $normalizer->normalizeLetters(
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
    // Arabic::normalizeLetters() static facade
    // =========================================================================

    public function test_static_normalize_letters_defaults_fold_alef_and_waw_hamza(): void
    {
        // Default: HamzaPolicy::Fold (ؤ→و, ئ kept), normalizeAlef=true, normalizeAlefMaqsura=true
        $result = Arabic::normalizeLetters('أإآؤئى');
        // أإآ → ا, ؤ → و (Fold), ئ kept, ى → ي
        $this->assertSame('اااوئي', $result);
    }

    public function test_static_normalize_letters_fold_all(): void
    {
        $result = Arabic::normalizeLetters('رئيس', hamza: HamzaPolicy::FoldAll);
        $this->assertSame('رييس', $result);
    }

    // =========================================================================
    // Slug generation — unicode()
    // =========================================================================

    public function test_unicode_slug_basic(): void
    {
        $result = Arabic::unicodeSlug('قائمة على جدران');
        $this->assertSame('قائمة-على-جدران', $result);
    }

    public function test_unicode_slug_strips_diacritics(): void
    {
        $this->assertSame(
            'قائمة-رئيسية',
            Arabic::unicodeSlug('قائِمةٌ رَئِيسيّة')
        );
    }

    public function test_unicode_slug_preserves_ta_marbuta(): void
    {
        $this->assertStringContainsString('ة', Arabic::unicodeSlug('مدرسة النور'));
    }

    public function test_unicode_slug_preserves_hamza_above_ya(): void
    {
        $slug = Arabic::unicodeSlug('قائِمةٌ رَئِيسيّة على مَنْصّةِ الإدارة');

        $this->assertStringContainsString('ئ', $slug);
        $this->assertStringContainsString('رئيسية', $slug);
        $this->assertStringNotContainsString('رييسية', $slug);
    }

    public function test_unicode_slug_lowercases_latin(): void
    {
        $result = Arabic::unicodeSlug('Hello عالم');
        $this->assertStringContainsString('hello', $result);
    }

    public function test_unicode_slug_custom_separator(): void
    {
        $result = Arabic::unicodeSlug('مرحبا بالعالم', '_');
        $this->assertSame('مرحبا_بالعالم', $result);
    }

    public function test_unicode_slug_respects_max_words(): void
    {
        $text = 'واحد اثنان ثلاثة أربعة خمسة ستة سبعة ثمانية تسعة';
        $result = Arabic::unicodeSlug($text, '-', 3);
        $parts = explode('-', $result);
        $this->assertLessThanOrEqual(3, count($parts));
    }

    public function test_unicode_slug_respects_max_length(): void
    {
        $longText = 'كلمة طويلة جداً وهي تحتوي على نص عربي كثير ومتعدد الكلمات والجمل';
        $result = Arabic::unicodeSlug($longText, '-', 0, 20);
        $this->assertLessThanOrEqual(20, mb_strlen($result));
    }

    public function test_unicode_slug_empty_string(): void
    {
        $this->assertSame('', Arabic::unicodeSlug(''));
    }

    public function test_unicode_slug_only_special_chars(): void
    {
        $this->assertSame('', Arabic::unicodeSlug('!@#$%^&*'));
    }

    public function test_unicode_slug_mixed_arabic_and_numbers(): void
    {
        $result = Arabic::unicodeSlug('مقال رقم 42');
        $this->assertSame('مقال-رقم-42', $result);
    }

    public function test_unicode_slug_arabic_indic_digits_converted(): void
    {
        $result = Arabic::unicodeSlug('مقال رقم ٤٢');
        $this->assertSame('مقال-رقم-42', $result);
    }

    public function test_unicode_slug_is_idempotent(): void
    {
        $text = 'قائمة على جدران';
        $once = Arabic::unicodeSlug($text);
        $this->assertSame($once, Arabic::unicodeSlug($once));
    }

    public function test_unicode_slug_no_leading_or_trailing_separator(): void
    {
        $result = Arabic::unicodeSlug(' مرحبا ');
        $this->assertStringStartsWith('م', $result);
        $this->assertStringEndsWith('ا', $result);
    }

    public function test_unicode_slug_no_consecutive_separators(): void
    {
        $result = Arabic::unicodeSlug('مرحبا   ---   بالعالم');
        $this->assertStringNotContainsString('--', $result);
    }

    // =========================================================================
    // Slug generation — ascii()
    // =========================================================================

    public function test_ascii_slug_produces_only_ascii(): void
    {
        $result = Arabic::asciiSlug('مرحبا بالعالم');
        $this->assertMatchesRegularExpression('/^[a-z0-9\-]+$/', $result);
    }

    public function test_ascii_slug_basic_transliteration_non_empty(): void
    {
        $result = Arabic::asciiSlug('محمد');
        $this->assertNotEmpty($result);
        $this->assertMatchesRegularExpression('/^[a-z\-]+$/', $result);
    }

    public function test_ascii_slug_respects_max_words(): void
    {
        $result = Arabic::asciiSlug('واحد اثنان ثلاثة أربعة خمسة', '-', 2);
        $parts = explode('-', $result);
        $this->assertLessThanOrEqual(2, count($parts));
    }

    public function test_ascii_slug_respects_max_length(): void
    {
        $result = Arabic::asciiSlug('نص عربي طويل جداً وممتد', '-', 0, 10);
        $this->assertLessThanOrEqual(10, strlen($result));
    }

    public function test_ascii_slug_empty_string(): void
    {
        $this->assertSame('', Arabic::asciiSlug(''));
    }

    public function test_slug_mode_unicode_matches_unicode_method(): void
    {
        $unicode = Arabic::unicodeSlug('مرحبا');
        $mode = Arabic::slug('مرحبا', SlugMode::Unicode);
        $this->assertSame($unicode, $mode);
    }

    public function test_slug_mode_ascii_matches_ascii_method(): void
    {
        $ascii = Arabic::asciiSlug('مرحبا');
        $mode = Arabic::slug('مرحبا', SlugMode::Ascii);
        $this->assertSame($ascii, $mode);
    }

    // =========================================================================
    // Digit conversion
    // =========================================================================

    public function test_digits_to_latin_arabic_indic(): void
    {
        $this->assertSame('1234567890', Arabic::digitsToLatin('١٢٣٤٥٦٧٨٩٠'));
    }

    public function test_digits_to_latin_eastern_arabic(): void
    {
        $this->assertSame('1234567890', Arabic::digitsToLatin('۱۲۳۴۵۶۷۸۹۰'));
    }

    public function test_digits_to_latin_mixed_digit_types(): void
    {
        $this->assertSame('12', Arabic::digitsToLatin('١۲'));
    }

    public function test_digits_to_latin_leaves_latin_unchanged(): void
    {
        $this->assertSame('9876', Arabic::digitsToLatin('9876'));
    }

    public function test_digits_to_arabic_indic_from_latin(): void
    {
        $this->assertSame('١٢٣٤٥٦٧٨٩٠', Arabic::digitsToArabicIndic('1234567890'));
    }

    public function test_digits_to_arabic_indic_leaves_arabic_text_unchanged(): void
    {
        $result = Arabic::digitsToArabicIndic('عدد 5');
        $this->assertSame('عدد ٥', $result);
    }

    public function test_digits_to_eastern_arabic_from_latin(): void
    {
        $this->assertSame('۱۲۳۴۵۶۷۸۹۰', Arabic::digitsToEasternArabic('1234567890'));
    }

    public function test_normalize_digits_to_latin(): void
    {
        $this->assertSame('42', Arabic::normalizeDigits('٤٢', DigitSet::Latin));
    }

    public function test_normalize_digits_to_arabic_indic(): void
    {
        $this->assertSame('١٢٣', Arabic::normalizeDigits('123', DigitSet::ArabicIndic));
    }

    public function test_digits_round_trip_arabic_indic(): void
    {
        $original = '١٢٣';
        $latin = Arabic::digitsToLatin($original);
        $back = Arabic::digitsToArabicIndic($latin);
        $this->assertSame($original, $back);
    }

    public function test_digits_in_mixed_text_preserved(): void
    {
        $result = Arabic::digitsToLatin('العدد ١٢ من ٣٠');
        $this->assertSame('العدد 12 من 30', $result);
    }

    // =========================================================================
    // Diacritics stripping
    // =========================================================================

    public function test_strip_diacritics_basic(): void
    {
        $this->assertSame('محمد', Arabic::stripDiacritics('مُحَمَّدٌ'));
    }

    public function test_strip_diacritics_kasra(): void
    {
        $this->assertSame('كتاب', Arabic::stripDiacritics('كِتَابٌ'));
    }

    public function test_strip_diacritics_fatha_tanwin(): void
    {
        $this->assertSame('رجل', Arabic::stripDiacritics('رَجُلٌ'));
    }

    public function test_strip_diacritics_shadda(): void
    {
        $this->assertSame('مدرس', Arabic::stripDiacritics('مُدَرِّسٌ'));
    }

    public function test_strip_diacritics_full_bismillah(): void
    {
        $this->assertSame(
            'بسم الله الرحمن الرحيم',
            Arabic::stripDiacritics('بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ'),
        );
    }

    public function test_strip_diacritics_leaves_base_letters_intact(): void
    {
        // stripDiacritics removes vowel marks (fatha, sukun, damma…) but NOT
        // the hamza on أ — أ is a base letter, not a diacritic combining mark.
        $this->assertSame('أحمد', Arabic::stripDiacritics('أَحْمَدُ'));
    }

    public function test_strip_diacritics_empty_string(): void
    {
        $this->assertSame('', Arabic::stripDiacritics(''));
    }

    public function test_strip_diacritics_no_diacritics_unchanged(): void
    {
        $this->assertSame('مرحبا', Arabic::stripDiacritics('مرحبا'));
    }

    public function test_strip_diacritics_is_idempotent(): void
    {
        $text = 'مُحَمَّدٌ';
        $once = Arabic::stripDiacritics($text);
        $this->assertSame($once, Arabic::stripDiacritics($once));
    }

    // =========================================================================
    // Tatweel stripping
    // =========================================================================

    public function test_strip_tatweel_basic(): void
    {
        $this->assertSame('العرب', Arabic::stripTatweel('العـــرب'));
    }

    public function test_strip_tatweel_single(): void
    {
        $this->assertSame('كتاب', Arabic::stripTatweel('كتـاب'));
    }

    public function test_strip_tatweel_multiple_runs_in_sentence(): void
    {
        $this->assertSame('جميل جداً', Arabic::stripTatweel('جميـل جـداً'));
    }

    public function test_strip_tatweel_no_tatweel_unchanged(): void
    {
        $this->assertSame('مرحبا', Arabic::stripTatweel('مرحبا'));
    }

    public function test_strip_tatweel_empty_string(): void
    {
        $this->assertSame('', Arabic::stripTatweel(''));
    }

    public function test_strip_tatweel_alias_stripe_tatweel(): void
    {
        $this->assertSame(
            Arabic::stripTatweel('العـرب'),
            Arabic::stripeTatweel('العـرب'),
        );
    }

    public function test_strip_tatweel_is_idempotent(): void
    {
        $text = 'العـرب';
        $once = Arabic::stripTatweel($text);
        $this->assertSame($once, Arabic::stripTatweel($once));
    }

    // =========================================================================
    // Safe filename
    // =========================================================================

    public function test_safe_filename_basic_arabic(): void
    {
        $result = Arabic::safeFilename('ملف عربي.pdf');
        $this->assertStringEndsWith('.pdf', $result);
        $this->assertStringNotContainsString(' ', $result);
    }

    public function test_safe_filename_preserves_extension(): void
    {
        $result = Arabic::safeFilename('كتاب.docx');
        $this->assertStringEndsWith('.docx', $result);
    }

    public function test_safe_filename_empty_base_uses_fallback(): void
    {
        // '.pdf' has its dot at position 0, so the extension guard (lastDot > 0)
        // treats the whole string as the base. The slug of '.pdf' yields 'pdf'.
        $result = Arabic::safeFilename('.pdf');
        $this->assertSame('pdf', $result);
    }

    public function test_safe_filename_strips_diacritics_from_base(): void
    {
        $result = Arabic::safeFilename('مُحَمَّدٌ.txt');
        $this->assertStringNotContainsString('ُ', $result);
        $this->assertStringEndsWith('.txt', $result);
    }

    public function test_safe_filename_no_extension(): void
    {
        $result = Arabic::safeFilename('مستند بلا امتداد');
        $this->assertNotEmpty($result);
        $this->assertStringNotContainsString(' ', $result);
    }

    public function test_safe_filename_strips_tatweel_from_base(): void
    {
        $result = Arabic::safeFilename('ملـف.txt');
        $this->assertStringNotContainsString('ـ', $result);
    }

    // =========================================================================
    // Excerpt
    // =========================================================================

    public function test_excerpt_basic_truncation(): void
    {
        $text = 'هذا نص عربي طويل يحتوي على كلمات كثيرة ومتعددة';
        $result = Arabic::excerpt($text, 20);
        // Result must be shorter than original
        $this->assertLessThan(mb_strlen($text), mb_strlen($result));
    }

    public function test_excerpt_short_text_unchanged(): void
    {
        $text = 'نص قصير';
        $this->assertSame($text, Arabic::excerpt($text, 200));
    }

    public function test_excerpt_strips_html(): void
    {
        $html = '<p>مرحبا <strong>بالعالم</strong></p>';
        $result = Arabic::excerpt($html, 200);
        $this->assertStringNotContainsString('<p>', $result);
        $this->assertStringNotContainsString('<strong>', $result);
    }

    public function test_excerpt_custom_end_marker(): void
    {
        $text = 'نص طويل جداً يحتاج إلى اقتطاع مناسب في مكان معين من هذا النص الطويل';
        $result = Arabic::excerpt($text, 10, '…');
        $this->assertStringEndsWith('…', $result);
    }

    // =========================================================================
    // Name normalization
    // =========================================================================

    public function test_name_basic(): void
    {
        $result = Arabic::name('محمد   علي');
        $this->assertSame('محمد علي', $result);
    }

    public function test_name_strips_tatweel(): void
    {
        $result = Arabic::name('مُحمـد');
        $this->assertStringNotContainsString('ـ', $result);
    }

    public function test_name_respects_max_words(): void
    {
        $result = Arabic::name('محمد علي عبد الله الحسن', 2);
        $parts = explode(' ', trim($result));
        $this->assertLessThanOrEqual(2, count($parts));
    }

    public function test_name_handles_single_word(): void
    {
        $result = Arabic::name('فاطمة');
        $this->assertSame('فاطمة', $result);
    }

    // =========================================================================
    // Punctuation
    // =========================================================================

    public function test_fix_punctuation_returns_string(): void
    {
        $result = Arabic::fixPunctuation('مرحبا، كيف حالك؟');
        $this->assertIsString($result);
        $this->assertStringContainsString('مرحبا', $result);
    }

    public function test_normalize_conjunction_waw_returns_string(): void
    {
        $result = Arabic::normalizeConjunctionWaw('محمد وعلي');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    // =========================================================================
    // Strip ordered list prefixes
    // =========================================================================

    public function test_strip_ordered_list_prefixes_arabic_digits(): void
    {
        $input = "١. مرحبا\n٢. بالعالم";
        $result = Arabic::stripOrderedListPrefixes($input);
        $this->assertStringNotContainsString('١.', $result);
        $this->assertStringNotContainsString('٢.', $result);
        $this->assertStringContainsString('مرحبا', $result);
    }

    public function test_strip_ordered_list_prefixes_latin_digits(): void
    {
        $input = "1. أولاً\n2. ثانياً";
        $result = Arabic::stripOrderedListPrefixes($input);
        $this->assertStringNotContainsString('1.', $result);
        $this->assertStringContainsString('أولاً', $result);
    }

    // =========================================================================
    // Text cleaning — clean(), sanitize(), sanitizePlain(), sanitizeForSearch()
    // =========================================================================

    public function test_clean_strips_html_tags(): void
    {
        $result = Arabic::clean('<p>مرحبا</p>');
        $this->assertStringNotContainsString('<p>', $result);
        $this->assertStringContainsString('مرحبا', $result);
    }

    public function test_clean_decodes_html_entities(): void
    {
        $result = Arabic::clean('&amp; &lt;');
        $this->assertStringContainsString('&', $result);
    }

    public function test_sanitize_strips_diacritics_when_requested(): void
    {
        $result = Arabic::sanitize('مُحَمَّدٌ', stripDiacritics: true);
        $this->assertSame('محمد', $result);
    }

    public function test_sanitize_strips_tatweel_by_default(): void
    {
        $result = Arabic::sanitize('كتـاب');
        $this->assertStringNotContainsString('ـ', $result);
    }

    public function test_sanitize_with_policy_search_folds_ta_marbuta(): void
    {
        $result = Arabic::sanitize('مدرسةٌ', policy: ArabicPolicy::Search);
        $this->assertStringContainsString('ه', $result);
    }

    public function test_sanitize_plain_strips_diacritics_and_tatweel(): void
    {
        $result = Arabic::sanitizePlain('مُدَرِّسـ');
        $this->assertStringNotContainsString('ـ', $result);
        $this->assertStringNotContainsString('ِ', $result);
        $this->assertStringNotContainsString('ُ', $result);
    }

    public function test_sanitize_for_search_matches_search_key(): void
    {
        $input = 'أَحْمَدُ';
        $this->assertSame(Arabic::searchKey($input), Arabic::sanitizeForSearch($input));
    }

    // =========================================================================
    // Whitespace normalization
    // =========================================================================

    public function test_normalize_whitespace_collapses_spaces(): void
    {
        $this->assertSame('كلمة واحدة', Arabic::normalizeWhitespace('كلمة   واحدة'));
    }

    public function test_normalize_whitespace_preserves_newlines_when_flag_set(): void
    {
        $input = "سطر أول\n\nسطر ثاني";
        $result = Arabic::normalizeWhitespace($input, preserveNewLines: true);
        $this->assertStringContainsString("\n", $result);
    }

    public function test_normalize_inline_whitespace_collapses_tabs(): void
    {
        $result = Arabic::normalizeInlineWhitespace("كلمة\t\t واحدة");
        $this->assertSame('كلمة واحدة', $result);
    }

    public function test_deep_trim_removes_unicode_no_break_space(): void
    {
        $result = Arabic::deepTrim("\u{00A0}مرحبا\u{00A0}");
        $this->assertSame('مرحبا', $result);
    }

    public function test_strip_html_removes_tags_but_keeps_entities(): void
    {
        // stripHtml only removes tags; entity decoding is done by clean().
        $result = Arabic::stripHtml('<b>مرحبا</b> &amp; <i>بالعالم</i>');
        $this->assertSame('مرحبا &amp; بالعالم', $result);
    }

    public function test_clean_removes_tags_and_decodes_entities(): void
    {
        // clean() does both: strips tags AND decodes HTML entities.
        $result = Arabic::clean('<b>مرحبا</b> &amp; <i>بالعالم</i>');
        $this->assertStringContainsString('مرحبا', $result);
        $this->assertStringContainsString('&', $result);
        $this->assertStringNotContainsString('<b>', $result);
    }

    // =========================================================================
    // Unicode security cleaning
    // =========================================================================

    public function test_remove_invisible_removes_zero_width_space(): void
    {
        $text = "مر\u{200B}حبا";
        $this->assertSame('مرحبا', Arabic::removeInvisible($text));
    }

    public function test_remove_bidi_controls_removes_rlm_lrm(): void
    {
        $text = "\u{200F}مرحبا\u{200E}";
        $result = Arabic::removeBidiControls($text);
        $this->assertStringNotContainsString("\u{200F}", $result);
        $this->assertStringNotContainsString("\u{200E}", $result);
        $this->assertStringContainsString('مرحبا', $result);
    }

    public function test_security_clean_removes_invisible_and_bidi(): void
    {
        $text = "\u{200C}\u{202A}مرحبا\u{202C}\u{200D}";
        $result = Arabic::securityClean($text);
        $this->assertStringContainsString('مرحبا', $result);
        $this->assertStringNotContainsString("\u{200C}", $result);
        $this->assertStringNotContainsString("\u{202A}", $result);
    }

    // =========================================================================
    // Arabic detection — containsArabic(), isArabic(), arabicRatio()
    // =========================================================================

    public function test_contains_arabic_true_for_arabic_text(): void
    {
        $this->assertTrue(Arabic::containsArabic('مرحبا'));
    }

    public function test_contains_arabic_true_for_mixed_text(): void
    {
        $this->assertTrue(Arabic::containsArabic('Hello مرحبا'));
    }

    public function test_contains_arabic_false_for_latin_only(): void
    {
        $this->assertFalse(Arabic::containsArabic('Hello World'));
    }

    public function test_contains_arabic_false_for_empty_string(): void
    {
        $this->assertFalse(Arabic::containsArabic(''));
    }

    public function test_contains_arabic_false_for_numbers_only(): void
    {
        $this->assertFalse(Arabic::containsArabic('12345'));
    }

    public function test_is_arabic_true_for_pure_arabic(): void
    {
        $this->assertTrue(Arabic::isArabic('مرحبا'));
    }

    public function test_is_arabic_false_for_mixed_text(): void
    {
        $this->assertFalse(Arabic::isArabic('Hello مرحبا'));
    }

    public function test_is_arabic_true_for_arabic_with_latin_digits(): void
    {
        // isArabic() checks that all *letters* are Arabic letters.
        // Latin digits (0-9) are not letters, so they don't disqualify the text.
        $this->assertTrue(Arabic::isArabic('مرحبا 123'));
    }

    public function test_is_arabic_false_for_arabic_with_latin_letters(): void
    {
        $this->assertFalse(Arabic::isArabic('مرحبا abc'));
    }

    public function test_arabic_ratio_pure_arabic_is_one(): void
    {
        $this->assertEqualsWithDelta(1.0, Arabic::arabicRatio('مرحبا'), 0.001);
    }

    public function test_arabic_ratio_pure_latin_is_zero(): void
    {
        $this->assertEqualsWithDelta(0.0, Arabic::arabicRatio('Hello'), 0.001);
    }

    public function test_arabic_ratio_mixed_is_between_zero_and_one(): void
    {
        $ratio = Arabic::arabicRatio('مرحب Hello');
        $this->assertGreaterThan(0.0, $ratio);
        $this->assertLessThan(1.0, $ratio);
    }

    public function test_arabic_ratio_empty_returns_zero(): void
    {
        $this->assertEqualsWithDelta(0.0, Arabic::arabicRatio(''), 0.001);
    }

    // =========================================================================
    // inspect()
    // =========================================================================

    public function test_inspect_returns_all_expected_keys(): void
    {
        $result = Arabic::inspect('مرحبا');
        $expected = [
            'characters', 'words', 'arabic_ratio', 'has_arabic', 'is_arabic',
            'has_diacritics', 'has_tatweel', 'has_html', 'has_arabic_digits',
            'has_bidi_controls', 'has_invisible_chars', 'has_suspicious_unicode',
        ];
        foreach ($expected as $key) {
            $this->assertArrayHasKey($key, $result, "inspect() must return '{$key}' key");
        }
    }

    public function test_inspect_pure_arabic_flags(): void
    {
        $result = Arabic::inspect('مرحبا');
        $this->assertTrue($result['has_arabic']);
        $this->assertTrue($result['is_arabic']);
        $this->assertFalse($result['has_diacritics']);
        $this->assertFalse($result['has_tatweel']);
        $this->assertFalse($result['has_html']);
    }

    public function test_inspect_detects_diacritics(): void
    {
        $result = Arabic::inspect('مُحَمَّدٌ');
        $this->assertTrue($result['has_diacritics']);
    }

    public function test_inspect_detects_tatweel(): void
    {
        $result = Arabic::inspect('جميـل');
        $this->assertTrue($result['has_tatweel']);
    }

    public function test_inspect_detects_html(): void
    {
        $result = Arabic::inspect('<p>مرحبا</p>');
        $this->assertTrue($result['has_html']);
    }

    public function test_inspect_detects_arabic_indic_digits(): void
    {
        $result = Arabic::inspect('رقم ١٢٣');
        $this->assertTrue($result['has_arabic_digits']);
    }

    public function test_inspect_detects_bidi_controls(): void
    {
        $result = Arabic::inspect("\u{202A}مرحبا\u{202C}");
        $this->assertTrue($result['has_bidi_controls']);
    }

    public function test_inspect_detects_invisible_chars(): void
    {
        $result = Arabic::inspect("مر\u{200B}حبا");
        $this->assertTrue($result['has_invisible_chars']);
    }

    public function test_inspect_word_count(): void
    {
        $result = Arabic::inspect('مرحبا بالعالم');
        $this->assertSame(2, $result['words']);
    }

    public function test_inspect_word_count_single_word(): void
    {
        $result = Arabic::inspect('مرحبا');
        $this->assertSame(1, $result['words']);
    }

    // =========================================================================
    // String length — length(), graphemeLength(), unicodeLength(), byteLength()
    // =========================================================================

    public function test_grapheme_length_arabic(): void
    {
        $this->assertSame(5, Arabic::graphemeLength('مرحبا'));
    }

    public function test_grapheme_length_with_diacritics_counts_base_chars(): void
    {
        // مُحَمَّدٌ = 4 base Arabic letters (م ح م د)
        $this->assertSame(4, Arabic::graphemeLength('مُحَمَّدٌ'));
    }

    public function test_unicode_length_includes_diacritics_as_code_points(): void
    {
        // Diacritics add extra code points
        $this->assertGreaterThan(4, Arabic::unicodeLength('مُحَمَّدٌ'));
    }

    public function test_byte_length_arabic_utf8_two_bytes_per_char(): void
    {
        // Arabic chars are 2 bytes each in UTF-8
        $this->assertSame(10, Arabic::byteLength('مرحبا'));
    }

    public function test_length_with_grapheme_unit_matches_grapheme_length(): void
    {
        $this->assertSame(Arabic::graphemeLength('مرحبا'), Arabic::length('مرحبا', LengthUnit::Grapheme));
    }

    public function test_length_with_unicode_unit_matches_unicode_length(): void
    {
        $this->assertSame(Arabic::unicodeLength('مرحبا'), Arabic::length('مرحبا', LengthUnit::Unicode));
    }

    public function test_length_with_byte_unit_matches_byte_length(): void
    {
        $this->assertSame(Arabic::byteLength('مرحبا'), Arabic::length('مرحبا', LengthUnit::Byte));
    }

    // =========================================================================
    // substr() and limit()
    // =========================================================================

    public function test_substr_grapheme_from_start(): void
    {
        $this->assertSame('مرح', Arabic::substr('مرحبا', 0, 3));
    }

    public function test_substr_grapheme_from_offset(): void
    {
        $this->assertSame('با', Arabic::substr('مرحبا', 3, 2));
    }

    public function test_limit_truncates_and_appends_ellipsis(): void
    {
        $result = Arabic::limit('مرحبا بالعالم', 5, LengthUnit::Grapheme, '...');
        $this->assertStringEndsWith('...', $result);
    }

    public function test_limit_does_not_truncate_short_text(): void
    {
        $this->assertSame('مرحبا', Arabic::limit('مرحبا', 100));
    }

    // =========================================================================
    // Edge cases — empty, whitespace-only, single characters
    // =========================================================================

    public function test_all_normalize_policies_handle_empty_string(): void
    {
        $policies = [
            ArabicPolicy::Display,
            ArabicPolicy::Search,
            ArabicPolicy::Slug,
            ArabicPolicy::Strict,
            ArabicPolicy::Security,
        ];
        foreach ($policies as $policy) {
            $this->assertSame('', Arabic::normalize('', $policy), "Policy {$policy->value} must return '' for empty input");
        }
    }

    public function test_all_helpers_handle_empty_string(): void
    {
        $this->assertSame('', Arabic::searchKey(''));
        $this->assertSame('', Arabic::stripDiacritics(''));
        $this->assertSame('', Arabic::stripTatweel(''));
        $this->assertSame('', Arabic::digitsToLatin(''));
        $this->assertSame('', Arabic::digitsToArabicIndic(''));
        $this->assertSame('', Arabic::unicodeSlug(''));
        $this->assertSame('', Arabic::asciiSlug(''));
        $this->assertSame('', Arabic::removeInvisible(''));
        $this->assertSame('', Arabic::removeBidiControls(''));
        $this->assertSame('', Arabic::securityClean(''));
    }

    public function test_normalize_whitespace_only_collapses_to_empty(): void
    {
        $this->assertSame('', Arabic::normalize('   ', ArabicPolicy::Display));
    }

    public function test_single_arabic_character_display(): void
    {
        $this->assertSame('م', Arabic::normalize('م', ArabicPolicy::Display));
    }

    public function test_single_arabic_character_search_key(): void
    {
        $this->assertSame('م', Arabic::searchKey('م'));
    }

    // =========================================================================
    // Mixed Arabic–Latin–digit texts (real-world scenarios)
    // =========================================================================

    public function test_mixed_text_search_key_with_serial_number(): void
    {
        $result = Arabic::searchKey('رقم التسجيل: A2025-١٢');
        $this->assertStringContainsString('a2025-12', $result);
    }

    public function test_mixed_text_unicode_slug_no_spaces(): void
    {
        $result = Arabic::unicodeSlug('منتج Pro - الإصدار 3.0');
        $this->assertMatchesRegularExpression('/^[\p{L}\p{N}\-]+$/u', $result);
        $this->assertStringNotContainsString(' ', $result);
    }

    public function test_mixed_rtl_ltr_search_key_lowercases_latin(): void
    {
        $result = Arabic::searchKey('مرحبا Hello مرحبا');
        $this->assertSame('مرحبا hello مرحبا', $result);
    }

    // =========================================================================
    // Real-world Arabic text scenarios
    // =========================================================================

    public function test_news_headline_unicode_slug(): void
    {
        $headline = 'البنك المركزي يرفع أسعار الفائدة للمرة الثالثة';
        $slug = Arabic::unicodeSlug($headline);
        $this->assertStringNotContainsString(' ', $slug);
        $this->assertStringNotContainsString('ُ', $slug);
        $this->assertStringContainsString('البنك', $slug);
    }

    public function test_personal_name_display_normalization(): void
    {
        $name = 'أحمـد   محمد  علي';
        $result = Arabic::name($name);
        $this->assertStringNotContainsString('ـ', $result);
        $this->assertStringNotContainsString('  ', $result);
    }

    public function test_mumin_and_momin_are_equal_after_search_normalization(): void
    {
        // مؤمن (with hamza) and مومن (without) should match
        $this->assertSame(Arabic::searchKey('مؤمن'), Arabic::searchKey('مومن'));
    }

    public function test_raees_and_rayees_are_no_t_equal_after_search_normalization(): void
    {
        // رئيس (president) must NOT equal رييس
        $this->assertNotSame(Arabic::searchKey('رئيس'), Arabic::searchKey('رييس'));
    }

    public function test_search_key_folds_all_alef_variants_the_same(): void
    {
        $this->assertStringStartsWith('ا', Arabic::searchKey('أحمد'));
        $this->assertStringStartsWith('ا', Arabic::searchKey('إحسان'));
        $this->assertStringStartsWith('ا', Arabic::searchKey('آخر'));
    }

    public function test_quran_verse_search_key_has_no_diacritics(): void
    {
        $verse = 'بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ';
        $result = Arabic::searchKey($verse);
        $this->assertStringNotContainsString('ِ', $result);
        $this->assertStringNotContainsString('ْ', $result);
        $this->assertStringNotContainsString('َ', $result);
    }

    public function test_product_slug_includes_latin_digits(): void
    {
        $title = 'حذاء رياضي أديداس - مقاس 42';
        $slug = Arabic::unicodeSlug($title);
        $this->assertStringNotContainsString(' ', $slug);
        $this->assertStringContainsString('42', $slug);
    }

    public function test_user_bio_search_key_consistent_regardless_of_diacritics(): void
    {
        $bio1 = 'مُطَوِّر بَرمجيّات مُتَخَصِّص في تطوير تطبيقات الويب';
        $bio2 = 'مطور برمجيات متخصص في تطوير تطبيقات الويب';
        $this->assertSame(Arabic::searchKey($bio1), Arabic::searchKey($bio2));
    }

    // =========================================================================
    // Profanity filter (containsBadWords)
    // =========================================================================

    public function test_contains_bad_words_with_explicit_word_list_match(): void
    {
        $this->assertTrue(Arabic::containsBadWords('هذا نص سيء', ['سيء']));
    }

    public function test_contains_bad_words_false_when_no_match(): void
    {
        $this->assertFalse(Arabic::containsBadWords('نص عادي', ['سيء']));
    }

    public function test_contains_bad_words_empty_list_returns_false(): void
    {
        $this->assertFalse(Arabic::containsBadWords('أي نص', []));
    }

    public function test_contains_bad_words_empty_text_returns_false(): void
    {
        $this->assertFalse(Arabic::containsBadWords('', ['سيء']));
    }
}
