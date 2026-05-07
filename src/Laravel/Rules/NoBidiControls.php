<?php

declare(strict_types=1);

namespace ArabicSupport\Laravel\Rules;

use ArabicSupport\Cleaning\UnicodeSecurityCleaner;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validates that a value does not contain bidirectional Unicode control characters.
 *
 * Bidi controls can visually reorder text and may be unsafe in usernames,
 * slugs, filenames, public comments, and admin-managed text.
 */
final class NoBidiControls implements ValidationRule
{
    /**
     * Create the rule with an optional Unicode security cleaner.
     */
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

        if ($this->cleaner->hasBidiControls($value)) {
            $fail('The :attribute contains unsafe bidirectional control characters.');
        }
    }
}
