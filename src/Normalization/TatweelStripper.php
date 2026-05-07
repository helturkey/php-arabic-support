<?php

declare(strict_types=1);

namespace ArabicSupport\Normalization;

use ArabicSupport\Patterns\ArabicPatterns;

/**
 * Strips Arabic tatweel/kashida characters.
 */
final class TatweelStripper
{
    public function strip(string $text): string
    {
        return preg_replace(ArabicPatterns::tatweel(), '', $text) ?: '';
    }

    public function has(string $text): bool
    {
        return preg_match(ArabicPatterns::tatweel(), $text) === 1;
    }
}
