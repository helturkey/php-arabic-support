<?php

declare(strict_types=1);

namespace ArabicSupport\Laravel\Rules;

use ArabicSupport\Patterns\ArabicPatterns;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validates that a value is a general Arabic personal/display name.
 */
final class ArabicName implements ValidationRule
{
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
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value)) {
            $fail('The :attribute must be a valid Arabic name.');

            return;
        }

        $value = trim($value);

        if ($value === '') {
            $fail('The :attribute must be a valid Arabic name.');

            return;
        }

        if (preg_match(ArabicPatterns::arabic(), $value) !== 1) {
            $fail('The :attribute must contain Arabic characters.');

            return;
        }

        if (preg_match(ArabicPatterns::arabicName(), $value) !== 1) {
            $fail('The :attribute contains unsupported characters.');
        }
    }
}
