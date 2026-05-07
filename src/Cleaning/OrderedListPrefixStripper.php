<?php

declare(strict_types=1);

namespace ArabicSupport\Cleaning;

use ArabicSupport\Patterns\ArabicPatterns;

/**
 * Removes ordered-list prefixes from the beginning of each line.
 */
final class OrderedListPrefixStripper
{
    /**
     * Strip ordered-list-like prefixes from the beginning of every line.
     *
     * Supported examples: 1. item, 1- item, (1) item, ١. item, ۱- item.
     */
    public function strip(string $text): string
    {
        return preg_replace(ArabicPatterns::orderedListPrefix(), '', $text) ?: '';
    }
}
