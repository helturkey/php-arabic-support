<?php

declare(strict_types=1);

namespace ArabicSupport\Cleaning;

use ArabicSupport\Patterns\ArabicPatterns;

/**
 * Detects and removes invisible, bidirectional, and control Unicode characters.
 *
 * This cleaner is useful for usernames, slugs, filenames, search input,
 * public comments, and imported text where hidden Unicode controls may be unsafe.
 */
final class UnicodeSecurityCleaner
{
    /**
     * Remove bidirectional Unicode control characters.
     */
    public function removeBidiControls(string $text): string
    {
        return preg_replace(ArabicPatterns::bidiControls(), '', $text) ?? $text;
    }

    /**
     * Remove zero-width Unicode characters.
     */
    public function removeZeroWidthCharacters(string $text): string
    {
        return preg_replace(ArabicPatterns::zeroWidth(), '', $text) ?? $text;
    }

    /**
     * Remove Unicode control characters.
     *
     * By default, normal new lines are preserved.
     */
    public function removeControlCharacters(string $text, bool $keepNewLines = true): string
    {
        $pattern = $keepNewLines
            ? ArabicPatterns::controlExceptNewLines()
            : ArabicPatterns::allControlCharacters();

        return preg_replace($pattern, '', $text) ?? $text;
    }

    /**
     * Remove all invisible and security-sensitive Unicode characters.
     */
    public function removeInvisibleCharacters(string $text, bool $keepNewLines = true): string
    {
        $text = $this->removeBidiControls($text);
        $text = $this->removeZeroWidthCharacters($text);

        return $this->removeControlCharacters($text, $keepNewLines);
    }

    /**
     * Alias for removeInvisibleCharacters().
     */
    public function clean(string $text, bool $keepNewLines = true): string
    {
        return $this->removeInvisibleCharacters($text, $keepNewLines);
    }

    /**
     * Determine whether the text contains bidirectional Unicode controls.
     */
    public function hasBidiControls(string $text): bool
    {
        return preg_match(ArabicPatterns::bidiControls(), $text) === 1;
    }

    /**
     * Determine whether the text contains zero-width Unicode characters.
     */
    public function hasZeroWidthCharacters(string $text): bool
    {
        return preg_match(ArabicPatterns::zeroWidth(), $text) === 1;
    }

    /**
     * Determine whether the text contains Unicode control characters.
     */
    public function hasControlCharacters(string $text, bool $keepNewLines = true): bool
    {
        $pattern = $keepNewLines
            ? ArabicPatterns::controlExceptNewLines()
            : ArabicPatterns::allControlCharacters();

        return preg_match($pattern, $text) === 1;
    }

    /**
     * Determine whether the text contains invisible or security-sensitive characters.
     */
    public function hasInvisibleCharacters(string $text, bool $keepNewLines = true): bool
    {
        return $this->hasBidiControls($text)
            || $this->hasZeroWidthCharacters($text)
            || $this->hasControlCharacters($text, $keepNewLines);
    }

    /**
     * Determine whether the text contains suspicious Unicode characters.
     */
    public function hasSuspiciousUnicode(string $text, bool $keepNewLines = true): bool
    {
        return $this->hasInvisibleCharacters($text, $keepNewLines);
    }
}
