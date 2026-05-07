<?php

declare(strict_types=1);

namespace ArabicSupport;

use ArabicSupport\Enums\ArabicPolicy;
use ArabicSupport\Enums\DigitSet;
use ArabicSupport\Enums\LengthUnit;
use ArabicSupport\Enums\SlugMode;

/**
 * Instance API used by the Laravel service container and facade.
 *
 * The methods intentionally mirror Arabic::* so Laravel users get the same
 * behavior through dependency injection or the facade.
 */
final class ArabicManager
{
    /** Create a fluent ArabicText pipeline. */
    public function text(string $text): ArabicText
    {
        return Arabic::text($text);
    }

    /** Clean HTML, Unicode controls, and whitespace. */
    public function clean(string $text): string
    {
        return Arabic::clean($text);
    }

    /** Sanitize text while preserving spelling, case, diacritics, and punctuation by default. */
    public function sanitize(
        string $text,
        ?ArabicPolicy $policy = null,
        bool $stripDiacritics = false,
        bool $stripTatweel = true,
        bool $lowercase = false,
        bool $keepPunctuation = true,
    ): string {
        return Arabic::sanitize($text, $policy, $stripDiacritics, $stripTatweel, $lowercase, $keepPunctuation);
    }

    /** Sanitize readable text and remove diacritics/tatweel without search folding. */
    public function sanitizePlain(string $text, bool $keepPunctuation = true): string
    {
        return Arabic::sanitizePlain($text, $keepPunctuation);
    }

    /** Sanitize text and normalize it for search/comparison use cases. */
    public function sanitizeForSearch(string $text): string
    {
        return Arabic::sanitizeForSearch($text);
    }

    /** Normalize text using the selected policy. */
    public function normalize(string $text, ArabicPolicy $policy = ArabicPolicy::Display): string
    {
        return Arabic::normalize($text, $policy);
    }

    /** Create a normalized search key. */
    public function searchKey(string $text): string
    {
        return Arabic::searchKey($text);
    }

    /** Generate a slug using the selected mode. */
    public function slug(string $text, SlugMode $mode = SlugMode::Unicode, string $separator = '-', int $maxWords = 8, int $maxLength = 180): string
    {
        return Arabic::slug($text, $mode, $separator, $maxWords, $maxLength);
    }

    /** Generate a readable Unicode slug. */
    public function unicodeSlug(string $text, string $separator = '-', int $maxWords = 8, int $maxLength = 180): string
    {
        return Arabic::unicodeSlug($text, $separator, $maxWords, $maxLength);
    }

    /** Generate an ASCII-only slug. */
    public function asciiSlug(string $text, string $separator = '-', int $maxWords = 8, int $maxLength = 180): string
    {
        return Arabic::asciiSlug($text, $separator, $maxWords, $maxLength);
    }

    /** Convert Arabic text to a Latin/ASCII approximation. */
    public function toAscii(string $text, bool $normalize = true): string
    {
        return Arabic::toAscii($text, $normalize);
    }

    /** Convert Arabic-Indic and Eastern Arabic/Persian digits to Latin digits. */
    public function digitsToLatin(string $text): string
    {
        return Arabic::digitsToLatin($text);
    }

    /** Convert all digits to Arabic-Indic digits. */
    public function digitsToArabicIndic(string $text): string
    {
        return Arabic::digitsToArabicIndic($text);
    }

    /** Convert all digits to Eastern Arabic/Persian digits. */
    public function digitsToEasternArabic(string $text): string
    {
        return Arabic::digitsToEasternArabic($text);
    }

    /** Normalize all digits to the selected digit set. */
    public function normalizeDigits(string $text, DigitSet $target = DigitSet::Latin): string
    {
        return Arabic::normalizeDigits($text, $target);
    }

    /** Strip Arabic diacritics. */
    public function stripDiacritics(string $text, bool $includeQuranMarks = true): string
    {
        return Arabic::stripDiacritics($text, $includeQuranMarks);
    }

    /** Strip tatweel/kashida. */
    public function stripTatweel(string $text): string
    {
        return Arabic::stripTatweel($text);
    }

    /** Backward-compatible alias for the older misspelled method name. */
    public function stripeTatweel(string $text): string
    {
        return Arabic::stripeTatweel($text);
    }

    /** Normalize whitespace with optional line-break preservation. */
    public function normalizeWhitespace(string $text, bool $preserveNewLines = false): string
    {
        return Arabic::normalizeWhitespace($text, $preserveNewLines);
    }

    /** Normalize all whitespace into a single inline space. */
    public function normalizeInlineWhitespace(string $text): string
    {
        return Arabic::normalizeInlineWhitespace($text);
    }

    /** Trim regular and invisible Unicode whitespace from both ends. */
    public function deepTrim(string $text): string
    {
        return Arabic::deepTrim($text);
    }

    /** Strip HTML tags and decode HTML entities. */
    public function stripHtml(string $text): string
    {
        return Arabic::stripHtml($text);
    }

    /** Strip ordered-list prefixes from lines. */
    public function stripOrderedListPrefixes(string $text): string
    {
        return Arabic::stripOrderedListPrefixes($text);
    }

    /** Create a clean excerpt. */
    public function excerpt(string $text, int $limit = 200, string $end = ' ...'): string
    {
        return Arabic::excerpt($text, $limit, $end);
    }

    /** Normalize a general Arabic name. */
    public function name(string $name, int $maxWords = 8, bool $applyCorrections = true, bool $normalizeAlefMaqsura = false): string
    {
        return Arabic::name($name, $maxWords, $applyCorrections, $normalizeAlefMaqsura);
    }

    /** Normalize punctuation spacing. */
    public function fixPunctuation(string $text): string
    {
        return Arabic::fixPunctuation($text);
    }

    /** Normalize conjunction waw spacing. */
    public function normalizeConjunctionWaw(string $text): string
    {
        return Arabic::normalizeConjunctionWaw($text);
    }

    /** Create a filesystem-safe filename. */
    public function safeFilename(string $filename, string $separator = '-'): string
    {
        return Arabic::safeFilename($filename, $separator);
    }

    /** Remove zero-width and invisible Unicode characters. */
    public function removeInvisible(string $text): string
    {
        return Arabic::removeInvisible($text);
    }

    /** Remove bidirectional Unicode control characters. */
    public function removeBidiControls(string $text): string
    {
        return Arabic::removeBidiControls($text);
    }

    /** Remove both invisible characters and bidi controls. */
    public function securityClean(string $text): string
    {
        return Arabic::securityClean($text);
    }

    /** Determine whether the text contains Arabic script. */
    public function containsArabic(string $text): bool
    {
        return Arabic::containsArabic($text);
    }

    /** Determine whether all letters in the text are Arabic letters. */
    public function isArabic(string $text): bool
    {
        return Arabic::isArabic($text);
    }

    /** Return the Arabic-letter ratio among all letters. */
    public function arabicRatio(string $text): float
    {
        return Arabic::arabicRatio($text);
    }

    /**
     * Inspect text and return diagnostics.
     *
     * @return array{characters:int, words:int, arabic_ratio:float, has_arabic:bool, is_arabic:bool, has_diacritics:bool, has_tatweel:bool, has_html:bool, has_arabic_digits:bool, has_bidi_controls:bool, has_invisible_chars:bool, has_suspicious_unicode:bool}
     */
    public function inspect(string $text): array
    {
        return Arabic::inspect($text);
    }

    /**
     * Determine whether the text contains any configured blocked words.
     *
     * @param  array<array-key, mixed>|string|null  $words  Words array, TXT path, JSON path, mixed source, or null.
     */
    public function containsBadWords(string $text, array|string|null $words = null): bool
    {
        return Arabic::containsBadWords($text, $words);
    }

    /** Return text length using the selected unit. */
    public function length(string $text, LengthUnit $unit = LengthUnit::Grapheme): int
    {
        return Arabic::length($text, $unit);
    }

    /** Return user-visible character length. */
    public function graphemeLength(string $text): int
    {
        return Arabic::graphemeLength($text);
    }

    /** Return Unicode code point length. */
    public function unicodeLength(string $text): int
    {
        return Arabic::unicodeLength($text);
    }

    /** Return raw UTF-8 byte length. */
    public function byteLength(string $text): int
    {
        return Arabic::byteLength($text);
    }

    /** Return a substring using the selected unit. */
    public function substr(string $text, int $start, ?int $length = null, LengthUnit $unit = LengthUnit::Grapheme): string
    {
        return Arabic::substr($text, $start, $length, $unit);
    }

    /** Limit text using the selected unit and append the suffix within the limit. */
    public function limit(string $text, int $limit, LengthUnit $unit = LengthUnit::Grapheme, string $end = '...'): string
    {
        return Arabic::limit($text, $limit, $unit, $end);
    }
}
