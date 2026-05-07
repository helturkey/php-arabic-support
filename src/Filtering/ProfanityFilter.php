<?php

declare(strict_types=1);

namespace ArabicSupport\Filtering;

use ArabicSupport\Normalization\ArabicNormalizer;

/**
 * Small configurable profanity detector for Arabic text.
 */
final class ProfanityFilter
{
    /** @var list<string> */
    private readonly array $words;

    /**
     * @param  list<string>  $words
     */
    public function __construct(array $words = [])
    {
        $this->words = $words;
    }

    /**
     * Determine whether the text contains one of the configured words.
     */
    public function contains(string $text): bool
    {
        if ($this->words === []) {
            return false;
        }

        $normalizer = new ArabicNormalizer;
        $normalized = $normalizer->searchKey($text);

        foreach ($this->words as $word) {
            $word = $normalizer->searchKey($word);
            $pattern = '/(?<!\p{L})'.preg_quote($word, '/').'(?!\p{L})/u';
            if (preg_match($pattern, $normalized) === 1) {
                return true;
            }
        }

        return false;
    }
}
