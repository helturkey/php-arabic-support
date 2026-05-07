<?php

declare(strict_types=1);

namespace ArabicSupport\Enums;

/**
 * Supported slug output modes.
 */
enum SlugMode: string
{
    /** Keep Arabic Unicode characters in the slug. */
    case Unicode = 'unicode';

    /** Transliterate Arabic characters to ASCII before creating the slug. */
    case Ascii = 'ascii';
}
