<?php

declare(strict_types=1);

namespace ArabicSupport\Laravel\Rules;

use ArabicSupport\Patterns\ArabicPatterns;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validates that a value is a valid Arabic/unicode slug.
 */
final class ArabicSlug implements ValidationRule
{
    /**
     * @param  non-empty-string  $separator
     */
    public function __construct(
        private readonly string $separator = '-',
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
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value)) {
            $fail('The :attribute must be a valid slug.');

            return;
        }

        if (preg_match(ArabicPatterns::slug($this->separator), $value) !== 1) {
            $fail('The :attribute must be a valid Arabic slug.');
        }
    }
}
