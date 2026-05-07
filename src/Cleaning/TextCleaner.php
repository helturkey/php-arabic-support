<?php

declare(strict_types=1);

namespace ArabicSupport\Cleaning;

use ArabicSupport\Compat\StringSupport;
use ArabicSupport\Enums\ArabicPolicy;
use ArabicSupport\Normalization\ArabicNormalizer;
use ArabicSupport\Normalization\DiacriticsStripper;
use ArabicSupport\Normalization\TatweelStripper;
use ArabicSupport\Patterns\ArabicPatterns;
use ArabicSupport\Punctuation\ArabicPunctuation;

/**
 * General text cleaning helpers for Arabic and mixed-language input.
 *
 * The methods in this class are intentionally conservative unless their name
 * says otherwise. For example, sanitize() cleans unsafe text but does not fold
 * Arabic letters for search unless an ArabicPolicy is passed explicitly.
 */
final class TextCleaner
{
    /** Characters commonly accepted in readable text sanitization. */
    private const COMMON_PUNCTUATION = '#: -.,،؛;?؟!()[]{}«»"\'';

    /**
     * Create a text cleaner with injectable helper services.
     */
    public function __construct(
        private readonly WhitespaceNormalizer $whitespace = new WhitespaceNormalizer,
        private readonly UnicodeSecurityCleaner $security = new UnicodeSecurityCleaner,
        private readonly TatweelStripper $tatweel = new TatweelStripper,
        private readonly DiacriticsStripper $diacritics = new DiacriticsStripper,
        private readonly ArabicNormalizer $normalizer = new ArabicNormalizer,
        private readonly ArabicPunctuation $punctuation = new ArabicPunctuation,
    ) {}

    /**
     * Strip HTML tags from text.
     *
     * When $preserveBlockSpaces is true, block-level tags are replaced with a
     * space before stripping so adjacent words are not accidentally joined.
     */
    public function stripHtml(string $text, bool $preserveBlockSpaces = true): string
    {
        if ($preserveBlockSpaces) {
            $text = preg_replace('/<\/(p|div|h[1-6]|li|br|blockquote|section|article)>/i', ' ', $text) ?: $text;
            $text = preg_replace('/<br\s*\/?>/i', ' ', $text) ?: $text;
        }

        return strip_tags($text);
    }

    /**
     * Keep Unicode letters, numbers, marks, spaces, and explicitly allowed characters.
     *
     * @param  string  $extra  Additional literal characters to preserve.
     */
    public function keepTextCharacters(string $text, string $extra = '', bool $keepPunctuation = true, string $replacement = ''): string
    {
        $text = $this->stripHtml($text);
        $extraCharacters = $extra.($keepPunctuation ? self::COMMON_PUNCTUATION : ' ');
        $extraClass = preg_quote($extraCharacters, '/');

        return preg_replace('/[^'.ArabicPatterns::LETTER.ArabicPatterns::NUMBER.ArabicPatterns::MARK.$extraClass.']/u', $replacement, $text) ?: '';
    }

    /** Keep characters suitable for slug preparation, replacing disallowed characters with spaces. */
    public function keepSlugCharacters(string $text): string
    {
        $text = $this->stripHtml($text);

        return preg_replace(ArabicPatterns::slugAllowed(), ' ', $text) ?: '';
    }

    /** Remove HTML, unsafe Unicode controls, and excessive inline whitespace. */
    public function clean(string $text): string
    {
        $text = $this->security->clean($text);
        $text = $this->stripHtml($text);

        return $this->whitespace->deepTrim($text);
    }

    /**
     * Sanitize text while preserving the user's spelling choices by default.
     *
     * Default behavior:
     * - strips HTML;
     * - removes invisible/bidi controls;
     * - keeps letters, numbers, marks, spaces, and common punctuation;
     * - strips tatweel by default;
     * - preserves diacritics, case, and Arabic letter forms.
     *
     * Pass ArabicPolicy::Search, or call sanitizeForSearch(), when the result is
     * intended for search/comparison and Arabic letters should be folded.
     */
    public function sanitize(
        string $text,
        ?ArabicPolicy $policy = null,
        bool $stripDiacritics = false,
        bool $stripTatweel = true,
        bool $lowercase = false,
        bool $keepPunctuation = true,
    ): string {
        $text = $this->security->clean($text);
        $text = $this->keepTextCharacters($text, keepPunctuation: $keepPunctuation, replacement: ' ');

        if ($stripTatweel) {
            $text = $this->tatweel->strip($text);
        }

        if ($stripDiacritics) {
            $text = $this->diacritics->strip($text, true);
        }

        if ($policy !== null) {
            $text = $this->normalizer->normalize($text, $policy);
        }

        $text = $this->whitespace->deepTrim($text);

        return $lowercase ? StringSupport::lower($text) : $text;
    }

    /**
     * Sanitize readable text and remove Arabic diacritics/tatweel, without search folding.
     */
    public function sanitizePlain(string $text, bool $keepPunctuation = true): string
    {
        return $this->sanitize(
            text: $text,
            stripDiacritics: true,
            stripTatweel: true,
            keepPunctuation: $keepPunctuation,
        );
    }

    /**
     * Sanitize text and normalize it using the Search policy.
     *
     * This method is intentionally aggressive and should be used for search,
     * moderation, and comparisons rather than display text.
     */
    public function sanitizeForSearch(string $text): string
    {
        return $this->sanitize(
            text: $text,
            policy: ArabicPolicy::Search,
            stripDiacritics: true,
            stripTatweel: true,
            lowercase: true,
            keepPunctuation: false,
        );
    }

    /** Normalize whitespace with optional line-break preservation. */
    public function normalizeWhitespace(string $text, bool $preserveNewLines = false): string
    {
        return $this->whitespace->normalize($text, $preserveNewLines);
    }

    /** Normalize all whitespace into a single inline space. */
    public function normalizeInlineWhitespace(string $text): string
    {
        return $this->whitespace->normalizeInline($text);
    }

    /**
     * Normalize standalone Arabic conjunction waw spacing.
     *
     * Examples: `محمد و علي` becomes `محمد وعلي`, and `و أحمد` becomes `وأحمد`.
     */
    public function normalizeConjunctionWaw(string $text): string
    {
        return $this->punctuation->normalizeConjunctionWaw($text);
    }

    /** Trim regular and invisible Unicode whitespace from the text. */
    public function deepTrim(string $text, bool $preserveNewLines = false): string
    {
        return $this->whitespace->deepTrim($text, $preserveNewLines);
    }
}
