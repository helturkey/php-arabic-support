<?php

declare(strict_types=1);

namespace ArabicSupport\Numbers;

use ArabicSupport\Enums\DigitSet;

/**
 * Converts Arabic, Persian, and Latin digit forms.
 */
final class ArabicDigits
{
    /** @var array<string,string> */
    private const TO_LATIN = [
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
    ];

    /** @var array<int,string> */
    private const TO_ARABIC_INDIC = [
        '0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤',
        '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩',
    ];

    /** @var array<int,string> */
    private const TO_EASTERN_ARABIC = [
        '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
        '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
    ];

    /** Convert Arabic-Indic and Eastern Arabic/Persian digits to Latin digits. */
    public function toLatin(string $text): string
    {
        return strtr($text, self::TO_LATIN);
    }

    /** Convert all digits in the string to Arabic-Indic digits. */
    public function toArabicIndic(string $text): string
    {
        return strtr($this->toLatin($text), self::TO_ARABIC_INDIC);
    }

    /** Convert all digits in the string to Eastern Arabic/Persian digits. */
    public function toEasternArabic(string $text): string
    {
        return strtr($this->toLatin($text), self::TO_EASTERN_ARABIC);
    }

    /** Normalize all digits in the string to the selected digit set. */
    public function normalize(string $text, DigitSet $target = DigitSet::Latin): string
    {
        return match ($target) {
            DigitSet::Latin => $this->toLatin($text),
            DigitSet::ArabicIndic => $this->toArabicIndic($text),
            DigitSet::EasternArabic => $this->toEasternArabic($text),
        };
    }

    /** Determine whether the text contains Arabic-Indic or Eastern Arabic digits. */
    public function hasArabicDigits(string $text): bool
    {
        return preg_match('/[\x{0660}-\x{0669}\x{06F0}-\x{06F9}]/u', $text) === 1;
    }
}
