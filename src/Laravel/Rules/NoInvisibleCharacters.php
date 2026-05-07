<?php

declare(strict_types=1);

namespace ArabicSupport\Laravel\Rules;

use ArabicSupport\Cleaning\UnicodeSecurityCleaner;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validates that a value does not contain invisible Unicode formatting characters.
 *
 * Normal spaces and line breaks are not rejected. This rule targets characters
 * such as zero-width marks, soft hyphen, byte-order mark, and related hidden
 * formatting controls.
 */
final class NoInvisibleCharacters implements ValidationRule
{
    public function __construct(
        private readonly UnicodeSecurityCleaner $cleaner = new UnicodeSecurityCleaner,
    ) {}

    /**
     * Validate the attribute.
     *
     * Empty values are ignored so this rule can be combined with "nullable".
     * Use Laravel's "required" rule when the field must be present.
     *
     * @param  Closure(string, string|null=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '' || ! is_string($value)) {
            return;
        }

        if ($this->cleaner->hasInvisibleCharacters($value)) {
            $fail('The :attribute contains unsupported invisible characters.');
        }
    }
}
