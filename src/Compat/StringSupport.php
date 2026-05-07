<?php

declare(strict_types=1);

namespace ArabicSupport\Compat;

use ArabicSupport\Enums\LengthUnit;

/**
 * Unicode-aware string helpers with Arabic-safe slicing.
 *
 * - Grapheme: uses grapheme_* when available, with PCRE \X fallback.
 * - Unicode: uses mb_* when available, with PCRE /./us fallback.
 * - Byte: uses byte limits, but avoids returning broken UTF-8 when the input is valid UTF-8.
 */
final class StringSupport
{
    public const ENCODING = 'UTF-8';

    /**
     * This class exposes static string helpers only.
     */
    private function __construct() {}

    /**
     * Lowercase a UTF-8 string when mbstring is available.
     */
    public static function lower(string $value): string
    {
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($value, self::ENCODING);
        }

        return strtolower($value);
    }

    /**
     * Trim characters from both sides of the string.
     */
    public static function trim(string $value, string $characters = " \n\r\t\v\0"): string
    {
        if ($characters === " \n\r\t\v\0" && function_exists('mb_trim')) {
            return mb_trim($value);
        }

        return trim($value, $characters);
    }

    /**
     * Trim characters from the right side of the string.
     */
    public static function rtrim(string $value, string $characters = " \n\r\t\v\0"): string
    {
        if ($characters === " \n\r\t\v\0" && function_exists('mb_rtrim')) {
            return mb_rtrim($value);
        }

        return rtrim($value, $characters);
    }

    /**
     * Trim characters from the left side of the string.
     */
    public static function ltrim(string $value, string $characters = " \n\r\t\v\0"): string
    {
        if ($characters === " \n\r\t\v\0" && function_exists('mb_ltrim')) {
            return mb_ltrim($value);
        }

        return ltrim($value, $characters);
    }

    /**
     * Determine whether the string starts with the given prefix.
     */
    public static function startsWith(string $value, string $prefix): bool
    {
        return str_starts_with($value, $prefix);
    }

    /**
     * Determine whether the string contains the given needle.
     */
    public static function contains(string $value, string $needle): bool
    {
        return str_contains($value, $needle);
    }

    /**
     * Determine whether the string ends with the given suffix.
     */
    public static function endsWith(string $value, string $suffix): bool
    {
        return str_ends_with($value, $suffix);
    }

    /**
     * Get string length using the selected unit.
     */
    public static function length(string $value, LengthUnit $unit = LengthUnit::Grapheme): int
    {
        return match ($unit) {
            LengthUnit::Grapheme => self::graphemeLength($value),
            LengthUnit::Unicode => self::unicodeLength($value),
            LengthUnit::Byte => self::byteLength($value),
        };
    }

    /**
     * Get a substring using the selected unit.
     */
    public static function substr(
        string $value,
        int $start,
        ?int $length = null,
        LengthUnit $unit = LengthUnit::Grapheme,
    ): string {
        return match ($unit) {
            LengthUnit::Grapheme => self::graphemeSubstr($value, $start, $length),
            LengthUnit::Unicode => self::unicodeSubstr($value, $start, $length),
            LengthUnit::Byte => self::byteSubstr($value, $start, $length),
        };
    }

    /**
     * Limit text to a maximum final length using the selected unit.
     *
     * The suffix is appended only when the text exceeds the limit, and its
     * length is included in the final maximum length.
     */
    public static function limit(
        string $value,
        int $limit,
        LengthUnit $unit = LengthUnit::Grapheme,
        string $end = '...',
    ): string {
        if ($limit <= 0) {
            return '';
        }

        if (self::length($value, $unit) <= $limit) {
            return $value;
        }

        $endLength = self::length($end, $unit);
        $available = max(0, $limit - $endLength);

        if ($available === 0) {
            return self::substr($end, 0, $limit, $unit);
        }

        $slice = self::substr($value, 0, $available, $unit);

        return self::safeRtrim($slice).$end;
    }

    /**
     * Count user-visible characters using grapheme clusters.
     *
     * Arabic combining marks remain attached to their base letters.
     */
    public static function graphemeLength(string $value): int
    {
        if (function_exists('grapheme_strlen')) {
            $length = grapheme_strlen($value);

            if ($length !== false) {
                return (int) $length;
            }
        }

        $clusters = self::graphemeClusters($value);

        if ($clusters !== []) {
            return count($clusters);
        }

        return self::unicodeLength($value);
    }

    /**
     * Get a substring by grapheme clusters.
     *
     * This keeps Arabic combining marks attached to their base letters.
     */
    public static function graphemeSubstr(string $value, int $start, ?int $length = null): string
    {
        if (function_exists('grapheme_substr')) {
            $result = $length === null
                ? grapheme_substr($value, $start)
                : grapheme_substr($value, $start, $length);

            if ($result !== false) {
                return $result;
            }
        }

        $clusters = self::graphemeClusters($value);

        if ($clusters === []) {
            return self::unicodeSubstr($value, $start, $length);
        }

        $slice = $length === null
            ? array_slice($clusters, $start)
            : array_slice($clusters, $start, $length);

        return implode('', $slice);
    }

    /**
     * Count Unicode code points.
     *
     * Arabic combining marks are counted separately in this unit.
     */
    public static function unicodeLength(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($value, self::ENCODING);
        }

        $chars = self::unicodeCharacters($value);

        if ($chars !== []) {
            return count($chars);
        }

        return self::byteLength($value);
    }

    /**
     * Get a substring by Unicode code points.
     */
    public static function unicodeSubstr(string $value, int $start, ?int $length = null): string
    {
        if (function_exists('mb_substr')) {
            return $length === null
                ? mb_substr($value, $start, null, self::ENCODING)
                : mb_substr($value, $start, $length, self::ENCODING);
        }

        $chars = self::unicodeCharacters($value);

        if ($chars === []) {
            return '';
        }

        $slice = $length === null
            ? array_slice($chars, $start)
            : array_slice($chars, $start, $length);

        return implode('', $slice);
    }

    /**
     * Count UTF-8 bytes.
     */
    public static function byteLength(string $value): int
    {
        return strlen($value);
    }

    /**
     * Get a byte-limited substring while avoiding broken UTF-8 output.
     *
     * If the input is valid UTF-8, the returned string will also be valid UTF-8.
     * Incomplete multibyte characters at the end of the byte range are dropped.
     */
    public static function byteSubstr(string $value, int $start, ?int $length = null): string
    {
        $byteLength = strlen($value);

        if ($start < 0) {
            $start = max(0, $byteLength + $start);
        }

        $end = $length === null
            ? $byteLength
            : ($length >= 0 ? $start + $length : $byteLength + $length);

        $start = max(0, min($start, $byteLength));
        $end = max($start, min($end, $byteLength));
        $length = $end - $start;

        if ($length <= 0) {
            return '';
        }

        if (function_exists('mb_strcut')) {
            return mb_strcut($value, $start, $length, self::ENCODING);
        }

        $slice = substr($value, $start, $length);

        if ($slice === '' || preg_match('//u', $slice) === 1) {
            return $slice;
        }

        while ($slice !== '' && preg_match('//u', $slice) !== 1) {
            $slice = substr($slice, 0, -1);
        }

        return $slice;
    }

    /**
     * Trim only ASCII whitespace from the right side.
     *
     * This avoids environment-specific multibyte trim behavior inside limit().
     */
    private static function safeRtrim(string $value): string
    {
        return rtrim($value, " \n\r\t\v\0");
    }

    /**
     * Split text into grapheme clusters.
     *
     * @return list<string>
     */
    private static function graphemeClusters(string $value): array
    {
        if ($value === '') {
            return [];
        }

        $count = preg_match_all('/\X/u', $value, $matches);

        if ($count === false || $matches[0] === []) {
            return [];
        }

        /** @var list<string> $clusters */
        $clusters = array_values($matches[0]);

        return $clusters;
    }

    /**
     * Split text into Unicode code points.
     *
     * @return list<string>
     */
    private static function unicodeCharacters(string $value): array
    {
        if ($value === '') {
            return [];
        }

        $count = preg_match_all('/./us', $value, $matches);

        if ($count === false || $matches[0] === []) {
            return [];
        }

        /** @var list<string> $chars */
        $chars = array_values($matches[0]);

        return $chars;
    }
}
