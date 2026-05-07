<?php

declare(strict_types=1);

namespace ArabicSupport\Patterns;

/**
 * Reusable Arabic/Unicode regex fragments and complete patterns.
 *
 * Keep fragments delimiter-free so consumers can compose their own PCRE patterns.
 */
final class ArabicPatterns
{
    public const ARABIC_SCRIPT = '\\p{Arabic}';

    public const LETTER = '\\p{L}';

    public const NUMBER = '\\p{N}';

    public const MARK = '\\p{M}';

    public const TATWEEL = '\\x{0640}';

    public const BASIC_DIACRITICS = '\\x{064B}-\\x{0652}';

    public const ARABIC_DIACRITICS = '\\x{0610}-\\x{061A}\\x{064B}-\\x{065F}\\x{0670}\\x{06D6}-\\x{06ED}';

    public const WESTERN_DIGITS = '0-9';

    public const ARABIC_INDIC_DIGITS = '\\x{0660}-\\x{0669}';

    public const EASTERN_ARABIC_DIGITS = '\\x{06F0}-\\x{06F9}';

    public const ALL_DIGITS = '0-9\\x{0660}-\\x{0669}\\x{06F0}-\\x{06F9}';

    public const BIDI_CONTROLS = '\\x{061C}\\x{200E}\\x{200F}\\x{202A}-\\x{202E}\\x{2066}-\\x{2069}';

    public const ZERO_WIDTH = '\\x{200B}-\\x{200D}\\x{2060}\\x{FEFF}';

    public const CONTROL_EXCEPT_NEW_LINES = '\\x{0000}-\\x{0008}\\x{000B}\\x{000C}\\x{000E}-\\x{001F}\\x{007F}-\\x{009F}';

    /**
     * This class contains reusable regex fragments and pattern factories.
     */
    private function __construct() {}

    /**
     * Wrap a delimiter-free regex fragment in a PCRE character class.
     */
    public static function charClass(string $fragment): string
    {
        return '['.$fragment.']';
    }

    /**
     * Return a pattern that strips Arabic/Latin ordered-list prefixes from line starts.
     */
    public static function orderedListPrefix(): string
    {
        $digits = self::charClass(self::ALL_DIGITS);

        return '/^\\s*(?:\\('.$digits.'+\\)|'.$digits.'+)[\\.\\-\\)\\:\\x{060C}\\s]*\\s*/um';
    }

    /**
     * Arabic name/display-name pattern.
     *
     * Allows Arabic script characters, combining marks, whitespace,
     * hyphens, dots, Arabic comma/thousands separator, and apostrophe-like marks.
     */
    public static function arabicName(): string
    {
        return '/^['
            .self::ARABIC_SCRIPT
            .self::MARK
            .'\s'
            .'\-'
            .'\.'
            .'،'
            .'٬'
            .'’'
            .'\''
            .'`'
            .']+$/u';
    }

    /**
     * Return a pattern that detects Arabic script characters.
     */
    public static function arabic(): string
    {
        return '/'.self::ARABIC_SCRIPT.'/u';
    }

    /**
     * Return a pattern that matches Arabic diacritics and optional Quranic marks.
     */
    public static function diacritics(bool $includeQuranMarks = true): string
    {
        return $includeQuranMarks
            ? '/'.self::charClass(self::ARABIC_DIACRITICS).'/u'
            : '/'.self::charClass(self::BASIC_DIACRITICS).'/u';
    }

    /**
     * Return a pattern that matches Arabic tatweel/kashida.
     */
    public static function tatweel(): string
    {
        return '/'.self::TATWEEL.'/u';
    }

    /**
     * Return a pattern that matches bidirectional Unicode controls.
     */
    public static function bidiControls(): string
    {
        return '/'.self::charClass(self::BIDI_CONTROLS).'/u';
    }

    /**
     * Return a pattern that matches zero-width Unicode formatting characters.
     */
    public static function zeroWidth(): string
    {
        return '/'.self::charClass(self::ZERO_WIDTH).'/u';
    }

    /**
     * Return a pattern that matches characters not allowed during slug preparation.
     */
    public static function slugAllowed(): string
    {
        return '/[^'.self::LETTER.self::NUMBER.self::MARK.'\\-\\s]+/u';
    }

    /**
     * Unicode slug pattern.
     *
     * Allows Unicode letters, Unicode numbers, combining marks, and a configurable separator.
     */
    public static function slug(string $separator = '-'): string
    {
        $separator = preg_quote($separator, '/');

        return '/^['.self::LETTER.self::NUMBER.self::MARK.']+(?:'.$separator.'['.self::LETTER.self::NUMBER.self::MARK.']+)*$/u';
    }

    /**
     * Unicode control characters except normal new lines.
     */
    public static function controlExceptNewLines(): string
    {
        return '/'.self::charClass(self::CONTROL_EXCEPT_NEW_LINES).'/u';
    }

    /**
     * All Unicode control characters.
     */
    public static function allControlCharacters(): string
    {
        return '/\p{C}/u';
    }

    /**
     * Invisible and security-sensitive Unicode characters.
     */
    public static function invisibleCharacters(): string
    {
        return '/'.self::charClass(
            self::BIDI_CONTROLS.
            self::ZERO_WIDTH.
            self::CONTROL_EXCEPT_NEW_LINES
        ).'/u';
    }
}
