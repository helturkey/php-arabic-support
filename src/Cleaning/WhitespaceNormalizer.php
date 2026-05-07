<?php

declare(strict_types=1);

namespace ArabicSupport\Cleaning;

use ArabicSupport\Compat\StringSupport;

/**
 * Normalizes Unicode whitespace, non-breaking spaces, and invisible separators.
 */
final class WhitespaceNormalizer
{
    /**
     * Normalize whitespace.
     *
     * By default this collapses all whitespace, including new lines, into a
     * single inline space. Set $preserveNewLines to true to clean each line while
     * keeping line breaks.
     */
    public function normalize(string $text, bool $preserveNewLines = false): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, StringSupport::ENCODING);
        $text = str_replace(["\xC2\xA0", '&nbsp;', '@nbsp;'], ' ', $text);

        if (! $preserveNewLines) {
            $text = preg_replace('/[\p{Z}\p{C}\t\r\n]+/u', ' ', $text) ?: $text;
            $text = preg_replace('/ {2,}/u', ' ', $text) ?: $text;

            return StringSupport::trim($text);
        }

        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = explode("\n", $text);

        foreach ($lines as $index => $line) {
            $line = preg_replace('/[\p{Z}\p{C}\t]+/u', ' ', $line) ?: $line;
            $line = preg_replace('/ {2,}/u', ' ', $line) ?: $line;
            $lines[$index] = StringSupport::trim($line);
        }

        return StringSupport::trim(implode("\n", $lines));
    }

    /** Collapse all whitespace into inline spaces. */
    public function normalizeInline(string $text): string
    {
        return $this->normalize($text, false);
    }

    /** Trim regular and invisible Unicode whitespace after normalization. */
    public function deepTrim(string $text, bool $preserveNewLines = false): string
    {
        $text = $this->normalize($text, $preserveNewLines);

        return StringSupport::trim(preg_replace('/(?! )[\p{Z}\p{C}]/u', '', $text) ?: $text);
    }
}
