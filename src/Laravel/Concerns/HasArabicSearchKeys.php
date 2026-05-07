<?php

declare(strict_types=1);

namespace ArabicSupport\Laravel\Concerns;

use ArabicSupport\Arabic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent concern that fills normalized Arabic search-key columns before saving.
 *
 * Override arabicSearchableColumns() in your model to customize the source/target columns.
 *
 * @mixin Model
 *
 * @phpstan-require-extends Model
 */
trait HasArabicSearchKeys
{
    /**
     * Register the Eloquent saving hook for Arabic search-key generation.
     */
    public static function bootHasArabicSearchKeys(): void
    {
        self::saving(static function (Model $model): void {
            if (! $model instanceof self) {
                return;
            }

            $model->fillArabicSearchKeys();
        });
    }

    /**
     * Fill configured Arabic search-key columns.
     */
    protected function fillArabicSearchKeys(): void
    {
        foreach ($this->arabicSearchableColumns() as $source => $target) {
            $value = $this->getAttribute($source);

            if ($value === null) {
                continue;
            }

            $this->setAttribute($target, Arabic::searchKey((string) $value));
        }
    }

    /**
     * Get source => target columns for Arabic search-key generation.
     *
     * @return array<string, string>
     */
    protected function arabicSearchableColumns(): array
    {
        return [];
    }

    /**
     * Add an exact-match condition after normalizing the incoming value.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function scopeWhereArabic(Builder $query, string $column, string $value): Builder
    {
        return $query->where($column, Arabic::searchKey($value));
    }

    /**
     * Add a contains condition after normalizing the incoming value.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function scopeWhereArabicContains(Builder $query, string $column, string $value): Builder
    {
        return $query->where($column, 'like', '%'.Arabic::searchKey($value).'%');
    }
}
