<?php

declare(strict_types=1);

namespace ArabicSupport\Normalization;

use ArabicSupport\Patterns\ArabicPatterns;

/**
 * Strips Arabic diacritics and optional Quranic annotation marks.
 */
final class DiacriticsStripper
{
    public function strip(string $text, bool $includeQuranMarks = true): string
    {
        return preg_replace(ArabicPatterns::diacritics($includeQuranMarks), '', $text) ?: '';
    }

    public function has(string $text): bool
    {
        return preg_match(ArabicPatterns::diacritics(true), $text) === 1;
    }
}
