<?php

declare(strict_types=1);

namespace ArabicSupport\Laravel\Concerns;

use ArabicSupport\Arabic;
use ArabicSupport\Enums\SlugMode;
use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent concern that fills a slug column from a source attribute before saving.
 *
 * Override arabicSlugSourceColumn(), arabicSlugTargetColumn(), arabicSlugMode(),
 * makeUniqueArabicSlug(), or arabicSlugUniqueResolver() in your model to customize
 * the behavior.
 *
 * @mixin Model
 *
 * @phpstan-require-extends Model
 */
trait HasArabicSlug
{
    /**
     * Register the Eloquent saving hook for Arabic slug generation.
     */
    public static function bootHasArabicSlug(): void
    {
        self::saving(static function (Model $model): void {
            if (! $model instanceof self) {
                return;
            }

            $model->fillArabicSlug();
        });
    }

    /**
     * Fill the configured slug column if it is empty.
     */
    protected function fillArabicSlug(): void
    {
        $source = $this->arabicSlugSourceColumn();
        $column = $this->arabicSlugTargetColumn();
        $mode = $this->arabicSlugMode();

        $sourceValue = $this->getAttribute($source);
        $currentSlug = $this->getAttribute($column);

        if (! is_scalar($sourceValue) || trim((string) $sourceValue) === '') {
            return;
        }

        if (is_scalar($currentSlug) && trim((string) $currentSlug) !== '') {
            return;
        }

        $base = Arabic::slug((string) $sourceValue, $mode);

        if ($base === '') {
            return;
        }

        $this->setAttribute($column, $this->makeUniqueArabicSlug($base, $column));
    }

    /**
     * Get the model attribute used as the source for slug generation.
     */
    protected function arabicSlugSourceColumn(): string
    {
        return 'title';
    }

    /**
     * Get the model attribute that stores the generated slug.
     */
    protected function arabicSlugTargetColumn(): string
    {
        return 'slug';
    }

    /**
     * Get the slug generation mode.
     */
    protected function arabicSlugMode(): SlugMode
    {
        return SlugMode::Unicode;
    }

    /**
     * Return a custom unique-slug resolver.
     *
     * The closure receives the base slug, the target slug column, and the current
     * model instance. Return null or an empty string to fall back to the default
     * numbered strategy.
     *
     * @return Closure(string, string, Model): (string|null)|null
     */
    protected function arabicSlugUniqueResolver(): ?Closure
    {
        return null;
    }

    /**
     * Generate a unique slug value for the target column.
     *
     * If arabicSlugUniqueResolver() returns a non-empty string, that value is used.
     * Otherwise, the default numbered strategy is used.
     */
    protected function makeUniqueArabicSlug(string $base, string $column): string
    {
        $resolver = $this->arabicSlugUniqueResolver();

        if ($resolver instanceof Closure) {
            $resolved = $resolver($base, $column, $this);

            if (is_string($resolved) && trim($resolved) !== '') {
                return $resolved;
            }
        }

        return $this->makeNumberedArabicSlug($base, $column);
    }

    /**
     * Generate a unique slug by appending an incrementing number.
     *
     * Example: product-title, product-title-2, product-title-3.
     */
    protected function makeNumberedArabicSlug(string $base, string $column): string
    {
        $slug = $base;
        $counter = 2;

        while ($this->arabicSlugExists($column, $slug)) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Determine whether a slug already exists for another model.
     */
    protected function arabicSlugExists(string $column, string $slug): bool
    {
        $query = self::query()->where($column, $slug);

        if ($this->exists) {
            $query->where($this->getKeyName(), '!=', $this->getKey());
        }

        return $query->exists();
    }
}
