<?php

declare(strict_types=1);

namespace ArabicSupport\Slug;

use ArabicSupport\Cleaning\TextCleaner;
use ArabicSupport\Compat\StringSupport;
use ArabicSupport\Enums\ArabicPolicy;
use ArabicSupport\Enums\SlugMode;
use ArabicSupport\Normalization\ArabicNormalizer;

/**
 * Generates Unicode and ASCII slugs from Arabic or mixed-language text.
 *
 * Note: the Unicode slug uses ArabicPolicy::Slug, which preserves spelling
 * identity (ئ, ى, ة, ؤ are NOT folded). This is intentionally different from
 * ArabicPolicy::Search. See ArabicNormalizer for policy details.
 */
final class ArabicSlugger
{
    public function __construct(
        private readonly TextCleaner $cleaner = new TextCleaner,
        private readonly ArabicNormalizer $normalizer = new ArabicNormalizer,
        private readonly ArabicTransliterator $transliterator = new ArabicTransliterator,
    ) {}

    /** Generate a slug using the selected output mode. */
    public function slug(string $text, SlugMode $mode = SlugMode::Unicode, string $separator = '-', int $maxWords = 8, int $maxLength = 180): string
    {
        return match ($mode) {
            SlugMode::Ascii => $this->ascii($text, $separator, $maxWords, $maxLength),
            SlugMode::Unicode => $this->unicode($text, $separator, $maxWords, $maxLength),
        };
    }

    /**
     * Generate a readable Unicode slug that preserves Arabic letter identity.
     *
     * Uses ArabicPolicy::Slug, which strips diacritics and tatweel but does
     * NOT fold ئ→ي, ى→ي, ة→ه, or ؤ→و. The result is human-readable and
     * suitable for use as a URL segment.
     */
    public function unicode(string $text, string $separator = '-', int $maxWords = 8, int $maxLength = 180): string
    {
        $text = $this->cleaner->keepSlugCharacters($text);
        $text = $this->normalizer->normalize($text, ArabicPolicy::Slug);
        $text = $this->limitWords($text, $maxWords);
        $text = preg_replace('/[^\p{L}\p{N}]+/u', $separator, $text) ?: '';
        $text = preg_replace('/'.preg_quote($separator, '/').'{2,}/u', $separator, $text) ?: '';
        $text = StringSupport::trim($text, $separator);
        $text = $this->limitLength($text, $maxLength, $separator);

        return StringSupport::lower($text);
    }

    /** Generate an ASCII-only slug by transliterating Arabic text. */
    public function ascii(string $text, string $separator = '-', int $maxWords = 8, int $maxLength = 180): string
    {
        $text = $this->transliterator->toAscii($text);
        $text = $this->limitWords($text, $maxWords);
        $text = preg_replace('/[^A-Za-z0-9]+/', $separator, $text) ?: '';
        $text = preg_replace('/'.preg_quote($separator, '/').'{2,}/', $separator, $text) ?: '';
        $text = StringSupport::trim($text, $separator);
        $text = $this->limitLength($text, $maxLength, $separator);

        return strtolower($text);
    }

    /** Limit text to a maximum number of words before slug replacement. */
    private function limitWords(string $text, int $maxWords): string
    {
        if ($maxWords <= 0) {
            return $text;
        }

        $words = preg_split('/\s+/u', StringSupport::trim($text), -1, PREG_SPLIT_NO_EMPTY);
        if (! is_array($words)) {
            return $text;
        }

        return implode(' ', array_slice($words, 0, $maxWords));
    }

    /** Limit the final slug length without ending with a partial segment. */
    private function limitLength(string $text, int $maxLength, string $separator): string
    {
        if ($maxLength <= 0 || StringSupport::length($text) <= $maxLength) {
            return $text;
        }

        $cut = StringSupport::substr($text, 0, $maxLength);
        $cut = preg_replace('/'.preg_quote($separator, '/').'[^'.preg_quote($separator, '/').']*$/u', '', $cut) ?: $cut;

        return StringSupport::trim($cut, $separator);
    }
}
