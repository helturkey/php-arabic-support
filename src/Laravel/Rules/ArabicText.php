<?php

declare(strict_types=1);

namespace ArabicSupport\Laravel\Rules;

use ArabicSupport\Arabic;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use InvalidArgumentException;

/**
 * Validates that a value contains a configurable minimum ratio of Arabic letters.
 *
 * This rule is useful for fields expected to be mostly Arabic, such as names,
 * titles, descriptions, and Arabic-only content inputs.
 */
final class ArabicText implements ValidationRule
{
    /**
     * @param  float  $minRatio  Minimum Arabic-letter ratio between 0.0 and 1.0.
     */
    public function __construct(
        private readonly float $minRatio = 0.6,
    ) {
        if ($minRatio < 0.0 || $minRatio > 1.0) {
            throw new InvalidArgumentException('The Arabic text minimum ratio must be between 0.0 and 1.0.');
        }
    }

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
            $fail('The :attribute must contain valid Arabic text.');

            return;
        }

        if (Arabic::arabicRatio($value) < $this->minRatio) {
            $fail('The :attribute must contain valid Arabic text.');
        }
    }
}
