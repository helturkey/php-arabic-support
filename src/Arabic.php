<?php

declare(strict_types=1);

namespace ArabicSupport;

use ArabicSupport\Cleaning\OrderedListPrefixStripper;
use ArabicSupport\Cleaning\TextCleaner;
use ArabicSupport\Cleaning\UnicodeSecurityCleaner;
use ArabicSupport\Compat\StringSupport;
use ArabicSupport\Enums\ArabicPolicy;
use ArabicSupport\Enums\DigitSet;
use ArabicSupport\Enums\HamzaPolicy;
use ArabicSupport\Enums\LengthUnit;
use ArabicSupport\Enums\SlugMode;
use ArabicSupport\Enums\TaMarbutaPolicy;
use ArabicSupport\Excerpt\TextExcerpt;
use ArabicSupport\Filtering\ProfanityFilter;
use ArabicSupport\Filtering\ProfanityWordsLoader;
use ArabicSupport\Names\ArabicNameNormalizer;
use ArabicSupport\Normalization\ArabicNormalizer;
use ArabicSupport\Normalization\DiacriticsStripper;
use ArabicSupport\Normalization\TatweelStripper;
use ArabicSupport\Numbers\ArabicDigits;
use ArabicSupport\Punctuation\ArabicPunctuation;
use ArabicSupport\Slug\ArabicSlugger;
use ArabicSupport\Slug\ArabicTransliterator;

/**
 * Static convenience API for PHP Arabic Support.
 *
 * The focused classes remain available for direct dependency injection, for
 * example ArabicSlugger, ArabicNormalizer, TextCleaner, and ArabicDigits.
 */
final class Arabic
{
    /**
     * Prevent instantiation; use static methods or the focused service classes instead.
     */
    private function __construct() {}

    /** Create a fluent ArabicText wrapper. */
    public static function text(string $text): ArabicText
    {
        return ArabicText::make($text);
    }

    /** Clean HTML, unsafe Unicode controls, and whitespace. */
    public static function clean(string $text): string
    {
        return (new TextCleaner)->clean($text);
    }

    /**
     * Sanitize text while preserving spelling, case, diacritics, and punctuation by default.
     *
     * Use named arguments when you need more aggressive behavior, or call
     * sanitizePlain() / sanitizeForSearch() for the common presets.
     */
    public static function sanitize(
        string $text,
        ?ArabicPolicy $policy = null,
        bool $stripDiacritics = false,
        bool $stripTatweel = true,
        bool $lowercase = false,
        bool $keepPunctuation = true,
    ): string {
        return (new TextCleaner)->sanitize(
            text: $text,
            policy: $policy,
            stripDiacritics: $stripDiacritics,
            stripTatweel: $stripTatweel,
            lowercase: $lowercase,
            keepPunctuation: $keepPunctuation,
        );
    }

    /** Sanitize readable text and remove diacritics/tatweel without search folding. */
    public static function sanitizePlain(string $text, bool $keepPunctuation = true): string
    {
        return (new TextCleaner)->sanitizePlain($text, $keepPunctuation);
    }

    /** Sanitize text and normalize it for search/comparison use cases. */
    public static function sanitizeForSearch(string $text): string
    {
        return (new TextCleaner)->sanitizeForSearch($text);
    }

    /** Normalize Unicode whitespace and trim the text. */
    public static function normalizeWhitespace(string $text, bool $preserveNewLines = false): string
    {
        return (new TextCleaner)->normalizeWhitespace($text, $preserveNewLines);
    }

    /** Normalize all whitespace into a single inline space. */
    public static function normalizeInlineWhitespace(string $text): string
    {
        return (new TextCleaner)->normalizeInlineWhitespace($text);
    }

    /** Trim regular and invisible Unicode whitespace from both ends. */
    public static function deepTrim(string $text): string
    {
        return (new TextCleaner)->deepTrim($text);
    }

    /** Strip HTML tags and decode HTML entities. */
    public static function stripHtml(string $text): string
    {
        return (new TextCleaner)->stripHtml($text);
    }

    /** Remove Arabic diacritics and optional Quranic marks. */
    public static function stripDiacritics(string $text, bool $includeQuranMarks = true): string
    {
        return (new DiacriticsStripper)->strip($text, $includeQuranMarks);
    }

    /** Remove Arabic tatweel/kashida characters. */
    public static function stripTatweel(string $text): string
    {
        return (new TatweelStripper)->strip($text);
    }

    /** Backward-compatible alias for the older misspelled method name. */
    public static function stripeTatweel(string $text): string
    {
        return self::stripTatweel($text);
    }

    /** Normalize text using a context-specific ArabicPolicy enum. */
    public static function normalize(string $text, ArabicPolicy $policy = ArabicPolicy::Display): string
    {
        return (new ArabicNormalizer)->normalize($text, $policy);
    }

    /** Create a normalized key suitable for search columns and comparisons. */
    public static function searchKey(string $text): string
    {
        return (new ArabicNormalizer)->searchKey($text);
    }

    /**
     * Normalize Arabic letter variants with explicit control over each transformation.
     *
     * Useful when preset policies do not fit, or when you need opt-in ئ→ي folding
     * via HamzaPolicy::FoldAll.
     *
     * Example:
     *   Arabic::normalizeLetters('رئيس', hamza: HamzaPolicy::FoldAll); // → رييس
     */
    public static function normalizeLetters(
        string $text,
        HamzaPolicy $hamza = HamzaPolicy::Fold,
        TaMarbutaPolicy $taMarbuta = TaMarbutaPolicy::Keep,
        bool $normalizeAlef = true,
        bool $normalizeAlefMaqsura = true,
        bool $normalizePersianLetters = true,
    ): string {
        return (new ArabicNormalizer)->normalizeLetters(
            $text, $hamza, $taMarbuta, $normalizeAlef, $normalizeAlefMaqsura, $normalizePersianLetters,
        );
    }

    /** Generate a slug using the selected SlugMode enum. */
    public static function slug(string $text, SlugMode $mode = SlugMode::Unicode, string $separator = '-', int $maxWords = 8, int $maxLength = 180): string
    {
        return (new ArabicSlugger)->slug($text, $mode, $separator, $maxWords, $maxLength);
    }

    /** Generate a readable Unicode slug that preserves Arabic letters. */
    public static function unicodeSlug(string $text, string $separator = '-', int $maxWords = 8, int $maxLength = 180): string
    {
        return (new ArabicSlugger)->unicode($text, $separator, $maxWords, $maxLength);
    }

    /** Generate an ASCII-only slug using Arabic transliteration. */
    public static function asciiSlug(string $text, string $separator = '-', int $maxWords = 8, int $maxLength = 180): string
    {
        return (new ArabicSlugger)->ascii($text, $separator, $maxWords, $maxLength);
    }

    /** Convert Arabic text to a Latin/ASCII approximation. */
    public static function toAscii(string $text, bool $normalize = true): string
    {
        return (new ArabicTransliterator)->toAscii($text, $normalize);
    }

    /** Convert Arabic-Indic and Eastern Arabic digits to Latin digits. */
    public static function digitsToLatin(string $text): string
    {
        return (new ArabicDigits)->toLatin($text);
    }

    /** Convert all digits to Arabic-Indic digits. */
    public static function digitsToArabicIndic(string $text): string
    {
        return (new ArabicDigits)->toArabicIndic($text);
    }

    /** Convert all digits to Eastern Arabic/Persian digits. */
    public static function digitsToEasternArabic(string $text): string
    {
        return (new ArabicDigits)->toEasternArabic($text);
    }

    /** Normalize all digits to the selected digit set. */
    public static function normalizeDigits(string $text, DigitSet $target = DigitSet::Latin): string
    {
        return (new ArabicDigits)->normalize($text, $target);
    }

    /** Remove ordered-list prefixes from the beginning of each line. */
    public static function stripOrderedListPrefixes(string $text): string
    {
        return (new OrderedListPrefixStripper)->strip($text);
    }

    /** Create a clean excerpt without cutting words in half. */
    public static function excerpt(string $htmlOrText, int $limit = 200, string $end = ' ...'): string
    {
        return (new TextExcerpt)->excerpt($htmlOrText, $limit, $end);
    }

    /** Normalize a general Arabic name for display. */
    public static function name(
        string $name,
        int $maxWords = 8,
        bool $applyCorrections = true,
        bool $normalizeAlefMaqsura = false,
    ): string {
        return (new ArabicNameNormalizer)->normalize($name, $maxWords, $applyCorrections, $normalizeAlefMaqsura);
    }

    /** Normalize punctuation spacing. */
    public static function fixPunctuation(string $text): string
    {
        return (new ArabicPunctuation)->normalize($text);
    }

    /** Normalize conjunction waw spacing. */
    public static function normalizeConjunctionWaw(string $text): string
    {
        return (new ArabicPunctuation)->normalizeConjunctionWaw($text);
    }

    /** Create a filesystem-friendly filename while preserving readable Arabic. */
    public static function safeFilename(string $filename, string $separator = '-'): string
    {
        $extension = '';
        $base = $filename;

        $lastDot = strrpos($filename, '.');
        if ($lastDot !== false && $lastDot > 0) {
            $extension = substr($filename, $lastDot);
            $base = substr($filename, 0, $lastDot);
        }

        $base = self::unicodeSlug($base, $separator, 0, 180);
        if ($base === '') {
            $base = 'file';
        }

        $extension = preg_replace('/[^A-Za-z0-9.]/', '', $extension) ?: '';

        return $base.$extension;
    }

    /** Remove zero-width and invisible Unicode characters. */
    public static function removeInvisible(string $text): string
    {
        return (new UnicodeSecurityCleaner)->removeInvisibleCharacters($text);
    }

    /** Remove bidirectional Unicode control characters. */
    public static function removeBidiControls(string $text): string
    {
        return (new UnicodeSecurityCleaner)->removeBidiControls($text);
    }

    /** Remove both invisible characters and bidi controls. */
    public static function securityClean(string $text): string
    {
        return (new UnicodeSecurityCleaner)->clean($text);
    }

    /** Determine whether the text contains Arabic script. */
    public static function containsArabic(string $text): bool
    {
        return (new ArabicInspector)->containsArabic($text);
    }

    /** Determine whether all letters in the text are Arabic letters. */
    public static function isArabic(string $text): bool
    {
        return (new ArabicInspector)->isArabic($text);
    }

    /** Return the Arabic-letter ratio among all letters. */
    public static function arabicRatio(string $text): float
    {
        return (new ArabicInspector)->arabicRatio($text);
    }

    /**
     * Inspect text and return useful diagnostics.
     *
     * @return array{characters:int, words:int, arabic_ratio:float, has_arabic:bool, is_arabic:bool, has_diacritics:bool, has_tatweel:bool, has_html:bool, has_arabic_digits:bool, has_bidi_controls:bool, has_invisible_chars:bool, has_suspicious_unicode:bool}
     */
    public static function inspect(string $text): array
    {
        return (new ArabicInspector)->inspect($text);
    }

    /**
     * Determine whether text contains blocked words.
     *
     * @param  list<string>|string|null  $words  Array of words, TXT path, JSON path, or null.
     */
    public static function containsBadWords(string $text, array|string|null $words = null): bool
    {
        $loadedWords = (new ProfanityWordsLoader)->load($words);

        return (new ProfanityFilter($loadedWords))->contains($text);
    }

    /**
     * Return text length using the selected unit.
     *
     * Grapheme is the default because it counts user-visible characters.
     */
    public static function length(string $text, LengthUnit $unit = LengthUnit::Grapheme): int
    {
        return StringSupport::length($text, $unit);
    }

    /** Return user-visible character length. */
    public static function graphemeLength(string $text): int
    {
        return StringSupport::graphemeLength($text);
    }

    /** Return Unicode code point length. */
    public static function unicodeLength(string $text): int
    {
        return StringSupport::unicodeLength($text);
    }

    /** Return raw UTF-8 byte length. */
    public static function byteLength(string $text): int
    {
        return StringSupport::byteLength($text);
    }

    /** Return a substring using the selected unit. */
    public static function substr(string $text, int $start, ?int $length = null, LengthUnit $unit = LengthUnit::Grapheme): string
    {
        return StringSupport::substr($text, $start, $length, $unit);
    }

    /** Limit text using the selected unit and append the suffix within the limit. */
    public static function limit(string $text, int $limit, LengthUnit $unit = LengthUnit::Grapheme, string $end = '...'): string
    {
        return StringSupport::limit($text, $limit, $unit, $end);
    }
}
