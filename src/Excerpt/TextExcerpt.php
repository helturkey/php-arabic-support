<?php

declare(strict_types=1);

namespace ArabicSupport\Excerpt;

use ArabicSupport\Cleaning\TextCleaner;
use ArabicSupport\Compat\StringSupport;

/**
 * Builds clean excerpts from HTML or plain text without cutting words.
 */
final class TextExcerpt
{
    /** @var TextCleaner */
    private $cleaner;

    /**
     * Create an excerpt builder with an optional text cleaner.
     */
    public function __construct(?TextCleaner $cleaner = null)
    {
        $this->cleaner = $cleaner ?: new TextCleaner;
    }

    /**
     * Build an excerpt from HTML or plain text without cutting the last word.
     */
    public function excerpt(string $htmlOrText, int $limit = 200, string $end = ' ...'): string
    {
        $text = $this->cleaner->stripHtml($htmlOrText, true);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, StringSupport::ENCODING);
        $text = str_replace("\xC2\xA0", ' ', $text);
        $text = $this->cleaner->normalizeWhitespace($text);

        if (StringSupport::length($text) <= $limit) {
            return $text;
        }

        $cut = StringSupport::substr($text, 0, $limit);
        $cut = preg_replace('/\s+\S*$/u', '', $cut) ?: $cut;

        return StringSupport::trim($cut).$end;
    }
}
