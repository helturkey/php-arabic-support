<?php

declare(strict_types=1);

namespace ArabicSupport;

use ArabicSupport\Cleaning\OrderedListPrefixStripper;
use ArabicSupport\Cleaning\TextCleaner;
use ArabicSupport\Cleaning\UnicodeSecurityCleaner;
use ArabicSupport\Compat\StringSupport;
use ArabicSupport\Enums\ArabicPolicy;
use ArabicSupport\Enums\LengthUnit;
use ArabicSupport\Enums\SlugMode;
use ArabicSupport\Excerpt\TextExcerpt;
use ArabicSupport\Normalization\ArabicNormalizer;
use ArabicSupport\Normalization\DiacriticsStripper;
use ArabicSupport\Normalization\TatweelStripper;
use ArabicSupport\Punctuation\ArabicPunctuation;
use ArabicSupport\Slug\ArabicSlugger;

/**
 * Fluent mutable text pipeline for common Arabic transformations.
 */
final class ArabicText
{
    /** Current text value carried through the pipeline. */
    private string $text;

    /**
     * Create a fluent text wrapper around the given string.
     */
    private function __construct(string $text)
    {
        $this->text = $text;
    }

    /** Create a fluent Arabic text pipeline. */
    public static function make(string $text): self
    {
        return new self($text);
    }

    /** Return the current pipeline value. */
    public function value(): string
    {
        return $this->text;
    }

    /** Convert the pipeline to its current text value. */
    public function __toString(): string
    {
        return $this->text;
    }

    /** Strip HTML tags and decode entities. */
    public function stripHtml(): self
    {
        $this->text = (new TextCleaner)->stripHtml($this->text);

        return $this;
    }

    /** Clean HTML, Unicode controls, and whitespace. */
    public function clean(): self
    {
        $this->text = (new TextCleaner)->clean($this->text);

        return $this;
    }

    /**
     * Sanitize text while preserving spelling, case, diacritics, and punctuation by default.
     */
    public function sanitize(
        ?ArabicPolicy $policy = null,
        bool $stripDiacritics = false,
        bool $stripTatweel = true,
        bool $lowercase = false,
        bool $keepPunctuation = true,
    ): self {
        $this->text = (new TextCleaner)->sanitize(
            text: $this->text,
            policy: $policy,
            stripDiacritics: $stripDiacritics,
            stripTatweel: $stripTatweel,
            lowercase: $lowercase,
            keepPunctuation: $keepPunctuation,
        );

        return $this;
    }

    /** Sanitize readable text and remove diacritics/tatweel without search folding. */
    public function sanitizePlain(bool $keepPunctuation = true): self
    {
        $this->text = (new TextCleaner)->sanitizePlain($this->text, $keepPunctuation);

        return $this;
    }

    /** Sanitize the current text and normalize it for search/comparison use cases. */
    public function sanitizeForSearch(): self
    {
        $this->text = (new TextCleaner)->sanitizeForSearch($this->text);

        return $this;
    }

    /** Normalize whitespace and trim the current text. */
    public function normalizeWhitespace(bool $preserveNewLines = false): self
    {
        $this->text = (new TextCleaner)->normalizeWhitespace($this->text, $preserveNewLines);

        return $this;
    }

    /** Normalize all whitespace into a single inline space. */
    public function normalizeInlineWhitespace(): self
    {
        $this->text = (new TextCleaner)->normalizeInlineWhitespace($this->text);

        return $this;
    }

    /** Remove Arabic diacritics from the current text. */
    public function stripDiacritics(bool $includeQuranMarks = true): self
    {
        $this->text = (new DiacriticsStripper)->strip($this->text, $includeQuranMarks);

        return $this;
    }

    /** Remove tatweel/kashida from the current text. */
    public function stripTatweel(): self
    {
        $this->text = (new TatweelStripper)->strip($this->text);

        return $this;
    }

    /** Normalize the current text according to an ArabicPolicy enum. */
    public function normalize(ArabicPolicy $policy = ArabicPolicy::Display): self
    {
        $this->text = (new ArabicNormalizer)->normalize($this->text, $policy);

        return $this;
    }

    /** Return the search key for the current text. */
    public function searchKey(): string
    {
        return (new ArabicNormalizer)->searchKey($this->text);
    }

    /** Remove ordered-list prefixes from every line. */
    public function stripOrderedListPrefixes(): self
    {
        $this->text = (new OrderedListPrefixStripper)->strip($this->text);

        return $this;
    }

    /** Normalize punctuation spacing. */
    public function fixPunctuation(): self
    {
        $this->text = (new ArabicPunctuation)->normalize($this->text);

        return $this;
    }

    /** Normalize standalone Arabic conjunction waw spacing. */
    public function normalizeConjunctionWaw(): self
    {
        $this->text = (new ArabicPunctuation)->normalizeConjunctionWaw($this->text);

        return $this;
    }

    /** Remove invisible and bidi Unicode controls. */
    public function securityClean(): self
    {
        $this->text = (new UnicodeSecurityCleaner)->clean($this->text);

        return $this;
    }

    /** Create a slug from the current text. */
    public function slug(SlugMode $mode = SlugMode::Unicode, string $separator = '-', int $maxWords = 8, int $maxLength = 180): string
    {
        return (new ArabicSlugger)->slug($this->text, $mode, $separator, $maxWords, $maxLength);
    }

    /** Create a readable Unicode slug from the current text. */
    public function unicodeSlug(string $separator = '-', int $maxWords = 8, int $maxLength = 180): string
    {
        return (new ArabicSlugger)->unicode($this->text, $separator, $maxWords, $maxLength);
    }

    /** Create an ASCII slug from the current text. */
    public function asciiSlug(string $separator = '-', int $maxWords = 8, int $maxLength = 180): string
    {
        return (new ArabicSlugger)->ascii($this->text, $separator, $maxWords, $maxLength);
    }

    /** Create a clean excerpt from the current text. */
    public function excerpt(int $limit = 200, string $end = ' ...'): string
    {
        return (new TextExcerpt)->excerpt($this->text, $limit, $end);
    }

    /**
     * Limit the current text using the selected length unit.
     *
     * Grapheme is the default because it is safest for visible text. Use
     * LengthUnit::Unicode or LengthUnit::Byte when a storage or protocol limit
     * requires that measurement.
     */
    public function limit(int $length, LengthUnit $unit = LengthUnit::Grapheme, string $end = '...'): self
    {
        $this->text = StringSupport::limit($this->text, $length, $unit, $end);

        return $this;
    }
}
