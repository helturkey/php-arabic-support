<?php

declare(strict_types=1);

namespace ArabicSupport\Punctuation;

use ArabicSupport\Compat\StringSupport;

/**
 * Normalizes punctuation spacing in Arabic and mixed-language text.
 */
final class ArabicPunctuation
{
    /**
     * Add a single space after Arabic/common punctuation when followed by text.
     */
    public function addSpaceAfterPunctuation(string $text): string
    {
        $text = preg_replace('/([:;؟؛\?،])(?=\S)/u', '$1 ', $text) ?? $text;
        $text = preg_replace('/(\.{2,}|…)(?=\S)/u', '$1 ', $text) ?? $text;

        return preg_replace('/[ \t]{2,}/u', ' ', $text) ?? $text;
    }

    /**
     * Normalize Arabic punctuation, bracket, quote, ellipsis, horizontal spaces,
     * and Arabic conjunction waw spacing.
     *
     * This method is conservative:
     * - it does not remove leading punctuation,
     * - it does not collapse line breaks,
     * - it only collapses normal spaces/tabs inside each line.
     */
    public function normalize(string $text): string
    {
        if (StringSupport::trim($text) === '') {
            return '';
        }

        $patterns = [
            // Remove extra horizontal space before punctuation.
            '/[ \t]+([؟?!،,؛;:.])/u',

            // Normalize bracket spacing using horizontal spaces only.
            '/\([ \t]+/u',
            '/[ \t]+\)/u',
            '/\[[ \t]+/u',
            '/[ \t]+\]/u',
            '/\{[ \t]+/u',
            '/[ \t]+\}/u',

            // Normalize quote spacing using horizontal spaces only.
            '/"[ \t]*(.*?)[ \t]*"/u',
            '/«[ \t]*(.*?)[ \t]*»/u',

            // Normalize ellipsis.
            '/\.{2,}|…/u',

            // Add one space after punctuation, except dot.
            // Newlines are preserved because the lookahead refuses whitespace.
            '/([؟?!،,؛;:])[ \t]*(?=[^\s؟?!،,؛;:.])/u',

            // Add one space after sentence-ending dot when followed by a non-digit letter-like char.
            '/\.(?=[^\s\d.])/u',

            // Collapse horizontal spaces only.
            '/[ \t]+/u',
        ];

        $replacements = [
            '$1',
            '(',
            ')',
            '[',
            ']',
            '{',
            '}',
            '"$1"',
            '«$1»',
            '...',
            '$1 ',
            '. ',
            ' ',
        ];

        $text = preg_replace($patterns, $replacements, $text) ?? $text;

        $text = $this->normalizeConjunctionWaw($text);

        return StringSupport::trim($text);
    }

    /**
     * Normalize Arabic conjunction waw spacing by attaching standalone waw to the following Arabic word.
     *
     * This removes the horizontal space after a standalone conjunction waw only when the waw appears:
     * - at the beginning of a line,
     * - after horizontal whitespace,
     * - or after punctuation plus optional horizontal spacing.
     *
     * It preserves the previous separator, so:
     * - "محمد و علي" becomes "محمد وعلي", not "محمدوعلي".
     * - ": وَ يُبَدِّلُ" becomes ": وَيُبَدِّلُ".
     * - "وَ دَمْعٍ" becomes "وَدَمْعٍ".
     *
     * Line breaks are preserved.
     */
    public function normalizeConjunctionWaw(string $text): string
    {
        $marks = '[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]*';

        return preg_replace(
            '/(^|[ \t؟?!،,؛;:."«»()\[\]{}-]+)(و'.$marks.')[ \t]+(?=\p{Arabic})/um',
            '$1$2',
            $text
        ) ?? $text;
    }

    /**
     * Remove leading punctuation at the beginning of each line.
     *
     * This is intentionally separate from normalize() because leading punctuation
     * can be meaningful in quotes, lists, dialogue, and imported content.
     */
    public function stripLeadingPunctuation(string $text): string
    {
        return preg_replace('/^[ \t]*[؟?!،,؛;:.]+[ \t]*/um', '', $text) ?? $text;
    }
}
