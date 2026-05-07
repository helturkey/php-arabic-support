<?php

declare(strict_types=1);

namespace ArabicSupport\Laravel\Facades;

use ArabicSupport\ArabicManager;
use ArabicSupport\ArabicText;
use ArabicSupport\Enums\ArabicPolicy;
use ArabicSupport\Enums\DigitSet;
use ArabicSupport\Enums\LengthUnit;
use ArabicSupport\Enums\SlugMode;
use Illuminate\Support\Facades\Facade;

/**
 * Laravel facade for the PHP Arabic Support manager.
 *
 * The @method declarations intentionally mirror ArabicManager so IDEs and
 * static-analysis tools can autocomplete facade calls without relying on
 * Laravel's dynamic proxying.
 *
 * @method static string clean(string $text)
 * @method static string sanitize(string $text, ?ArabicPolicy $policy = null, bool $stripDiacritics = false, bool $stripTatweel = true, bool $lowercase = false, bool $keepPunctuation = true)
 * @method static string sanitizePlain(string $text, bool $keepPunctuation = true)
 * @method static string sanitizeForSearch(string $text)
 * @method static string normalize(string $text, ArabicPolicy $policy = ArabicPolicy::Display)
 * @method static string searchKey(string $text)
 * @method static string slug(string $text, SlugMode $mode = SlugMode::Unicode, string $separator = '-', int $maxWords = 8, int $maxLength = 180)
 * @method static string unicodeSlug(string $text, string $separator = '-', int $maxWords = 8, int $maxLength = 180)
 * @method static string asciiSlug(string $text, string $separator = '-', int $maxWords = 8, int $maxLength = 180)
 * @method static string toAscii(string $text, bool $normalize = true)
 * @method static string stripDiacritics(string $text, bool $includeQuranMarks = true)
 * @method static string stripTatweel(string $text)
 * @method static string stripeTatweel(string $text)
 * @method static string normalizeWhitespace(string $text, bool $preserveNewLines = false)
 * @method static string normalizeInlineWhitespace(string $text)
 * @method static string deepTrim(string $text)
 * @method static string stripHtml(string $text)
 * @method static string stripOrderedListPrefixes(string $text)
 * @method static string excerpt(string $text, int $limit = 200, string $end = ' ...')
 * @method static string name(string $name, int $maxWords = 8, bool $applyCorrections = true, bool $normalizeAlefMaqsura = false)
 * @method static string fixPunctuation(string $text)
 * @method static string normalizeConjunctionWaw(string $text)
 * @method static string safeFilename(string $filename, string $separator = '-')
 * @method static array{characters:int, words:int, arabic_ratio:float, has_arabic:bool, is_arabic:bool, has_diacritics:bool, has_tatweel:bool, has_html:bool, has_arabic_digits:bool, has_bidi_controls:bool, has_invisible_chars:bool, has_suspicious_unicode:bool} inspect(string $text)
 * @method static int length(string $text, LengthUnit $unit = LengthUnit::Grapheme)
 * @method static int graphemeLength(string $text)
 * @method static int unicodeLength(string $text)
 * @method static int byteLength(string $text)
 * @method static string substr(string $text, int $start, ?int $length = null, LengthUnit $unit = LengthUnit::Grapheme)
 * @method static string limit(string $text, int $limit, LengthUnit $unit = LengthUnit::Grapheme, string $end = '...')
 * @method static string digitsToLatin(string $text)
 * @method static string digitsToArabicIndic(string $text)
 * @method static string digitsToEasternArabic(string $text)
 * @method static string normalizeDigits(string $text, DigitSet $target = DigitSet::Latin)
 * @method static string removeInvisible(string $text)
 * @method static string removeBidiControls(string $text)
 * @method static string securityClean(string $text)
 * @method static bool containsArabic(string $text)
 * @method static bool isArabic(string $text)
 * @method static float arabicRatio(string $text)
 * @method static bool containsBadWords(string $text, list<string> $words)
 * @method static ArabicText text(string $text)
 *
 * @see ArabicManager
 */
final class Arabic extends Facade
{
    /**
     * Return the Laravel container binding name used by the service provider.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'php-arabic';
    }
}
