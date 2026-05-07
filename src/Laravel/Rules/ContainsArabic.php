<?php

declare(strict_types=1);

namespace ArabicSupport\Laravel\Rules;

use ArabicSupport\Arabic;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final class ContainsArabic implements ValidationRule
{
    /**
     * Validate that the value contains at least one Arabic character.
     *
     * @param  Closure(string, string|null=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || ! Arabic::containsArabic($value)) {
            $fail('The :attribute must contain at least one Arabic character.');
        }
    }
}
