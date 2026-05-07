<?php

declare(strict_types=1);

namespace ArabicSupport\Laravel\Rules;

use ArabicSupport\Filtering\ProfanityFilter;
use ArabicSupport\Filtering\ProfanityWordsLoader;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validates that a value does not contain configured blocked words.
 */
final class NoBadWords implements ValidationRule
{
    /**
     * @param  list<string>|string|null  $words
     */
    public function __construct(
        private readonly array|string|null $words = null,
    ) {}

    /**
     * Validate the attribute.
     *
     * @param  Closure(string, string|null=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '' || ! is_string($value)) {
            return;
        }

        $words = (new ProfanityWordsLoader)->load(
            $this->words ?? $this->configuredWords()
        );

        if ((new ProfanityFilter($words))->contains($value)) {
            $fail('The :attribute contains unsupported words.');
        }
    }

    /**
     * Get blocked words from Laravel config.
     *
     * @return array<array-key, mixed>|string
     */
    private function configuredWords(): array|string
    {
        if (! function_exists('config')) {
            return [];
        }

        $words = \config('php-arabic-support.profanity.words', []);

        return is_array($words) || is_string($words) ? $words : [];
    }
}
