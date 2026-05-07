<?php

declare(strict_types=1);

namespace ArabicSupport;

use ArabicSupport\Cleaning\UnicodeSecurityCleaner;
use ArabicSupport\Compat\StringSupport;
use ArabicSupport\Normalization\DiacriticsStripper;
use ArabicSupport\Normalization\TatweelStripper;
use ArabicSupport\Numbers\ArabicDigits;
use ArabicSupport\Patterns\ArabicPatterns;

/**
 * Inspects Arabic or mixed-language text and returns diagnostics.
 *
 * @phpstan-type ArabicInspection array{
 *     characters:int,
 *     words:int,
 *     arabic_ratio:float,
 *     has_arabic:bool,
 *     is_arabic:bool,
 *     has_diacritics:bool,
 *     has_tatweel:bool,
 *     has_html:bool,
 *     has_arabic_digits:bool,
 *     has_bidi_controls:bool,
 *     has_invisible_chars:bool,
 *     has_suspicious_unicode:bool
 * }
 */
final class ArabicInspector
{
    /**
     * Return a compact diagnostics array for display, import validation, or debugging.
     *
     * @return ArabicInspection
     */
    public function inspect(string $text): array
    {
        $characters = StringSupport::length($text);
        $letters = $this->countLetters($text);
        $arabicLetters = $this->countArabicLetters($text);
        $security = new UnicodeSecurityCleaner;

        return [
            'characters' => $characters,
            'words' => $this->wordCount($text),
            'arabic_ratio' => $letters > 0 ? round($arabicLetters / $letters, 4) : 0.0,
            'has_arabic' => $this->containsArabic($text),
            'is_arabic' => $letters > 0 && $arabicLetters === $letters,
            'has_diacritics' => (new DiacriticsStripper)->has($text),
            'has_tatweel' => (new TatweelStripper)->has($text),
            'has_html' => $text !== strip_tags($text),
            'has_arabic_digits' => (new ArabicDigits)->hasArabicDigits($text),
            'has_bidi_controls' => $security->hasBidiControls($text),
            'has_invisible_chars' => $security->hasInvisibleCharacters($text),
            'has_suspicious_unicode' => $security->hasSuspiciousUnicode($text),
        ];
    }

    /** Determine whether the text contains at least one Arabic-script character. */
    public function containsArabic(string $text): bool
    {
        return preg_match(ArabicPatterns::arabic(), $text) === 1;
    }

    /** Determine whether every Unicode letter in the text is Arabic-script. */
    public function isArabic(string $text): bool
    {
        $letters = $this->countLetters($text);
        if ($letters === 0) {
            return false;
        }

        return $this->countArabicLetters($text) === $letters;
    }

    /** Return the Arabic-letter ratio among all Unicode letters in the text. */
    public function arabicRatio(string $text): float
    {
        $letters = $this->countLetters($text);
        if ($letters === 0) {
            return 0.0;
        }

        return round($this->countArabicLetters($text) / $letters, 4);
    }

    /** Count whitespace-separated words after Unicode-aware trimming. */
    private function wordCount(string $text): int
    {
        $words = preg_split('/\s+/u', StringSupport::trim($text), -1, PREG_SPLIT_NO_EMPTY);

        return is_array($words) ? count($words) : 0;
    }

    /** Count all Unicode letters in the text. */
    private function countLetters(string $text): int
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (! is_array($chars)) {
            return 0;
        }

        $count = 0;
        foreach ($chars as $char) {
            if (preg_match('/\p{L}/u', $char) === 1) {
                $count++;
            }
        }

        return $count;
    }

    /** Count Arabic-script letters in the text. */
    private function countArabicLetters(string $text): int
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (! is_array($chars)) {
            return 0;
        }

        $count = 0;
        foreach ($chars as $char) {
            if (preg_match('/\p{L}/u', $char) === 1 && preg_match(ArabicPatterns::arabic(), $char) === 1) {
                $count++;
            }
        }

        return $count;
    }
}
