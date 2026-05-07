<?php

declare(strict_types=1);

namespace ArabicSupport\Enums;

/**
 * Defines how text length and substring operations are measured.
 *
 * Grapheme is best for user-visible text. Unicode counts Unicode code points.
 * Byte counts UTF-8 bytes and is useful when working with byte-limited storage
 * or transport layers.
 */
enum LengthUnit: string
{
    /** User-visible characters, keeping a base letter and its marks together. */
    case Grapheme = 'grapheme';

    /** Unicode code points, similar to mb_strlen(..., 'UTF-8'). */
    case Unicode = 'unicode';

    /** Raw UTF-8 bytes, similar to strlen(). */
    case Byte = 'byte';
}
