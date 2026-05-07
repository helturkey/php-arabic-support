<?php

declare(strict_types=1);

namespace ArabicSupport\Punctuation;

use ArabicSupport\Compat\StringSupport;

/**
 * Normalizes punctuation spacing in Arabic and mixed-language text.
 */
final class ArabicPunctuation
{
    public function addSpaceAfterPunctuation(string $text): string
    {
        $text = preg_replace('/([:;؟؛\?،])(?=\S)/u', '$1 ', $text) ?: $text;
        $text = preg_replace('/(\.{2,}|…)(?=\S)/u', '$1 ', $text) ?: $text;

        return preg_replace('/[ \t]{2,}/u', ' ', $text) ?: $text;
    }

    public function normalize(string $text): string
    {
        if (StringSupport::trim($text) === '') {
            return '';
        }

        $patterns = [
            '/\s+([؟?!،,؛;:.])/u',
            '/\(\s+/',
            '/\s+\)/',
            '/\[\s+/',
            '/\s+\]/',
            '/\{\s+/',
            '/\s+\}/',
            '/"\s*(.*?)\s*"/u',
            '/«\s*(.*?)\s*»/u',
            '/\.{2,}|…/u',
            '/([؟?!،,؛;:])\s*(?=[^\s؟?!،,؛;:.])/u',
            '/\.(?=[^\s\d.])/u',
            '/\s+/u',
        ];

        $replacements = [
            '$1', '(', ')', '[', ']', '{', '}', '"$1"', '«$1»', '...', '$1 ', '. ', ' ',
        ];

        $text = preg_replace($patterns, $replacements, $text) ?: $text;

        return StringSupport::trim($text);
    }

    public function normalizeConjunctionWaw(string $text): string
    {
        $diacritics = '[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]';

        return preg_replace('/(?:(?<=\s)|^)و'.$diacritics.'*\s+/um', 'و', $text) ?: $text;
    }
}
