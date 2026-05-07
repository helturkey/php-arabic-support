<?php

declare(strict_types=1);

namespace ArabicSupport\Enums;

/**
 * Digit systems supported by PHP Arabic Support.
 */
enum DigitSet: string
{
    /** Western digits: 0123456789. */
    case Latin = 'latin';

    /** Arabic-Indic digits: ٠١٢٣٤٥٦٧٨٩. */
    case ArabicIndic = 'arabic_indic';

    /** Eastern Arabic/Persian digits: ۰۱۲۳۴۵۶۷۸۹. */
    case EasternArabic = 'eastern_arabic';
}
