<?php

declare(strict_types=1);

namespace ArabicSupport\Tests\Fixtures\Models;

use ArabicSupport\Arabic;
use ArabicSupport\Enums\SlugMode;
use ArabicSupport\Laravel\Concerns\HasArabicSearchKeys;
use ArabicSupport\Laravel\Concerns\HasArabicSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Fixture model that customizes slug uniqueness by appending writer names before
 * falling back to the default numbered strategy.
 *
 * @property int $id
 * @property string $title
 * @property string|null $writer
 * @property string|null $title_search
 * @property string|null $writer_search
 * @property string|null $slug
 *
 * @method static Builder query()
 * @method static self create(array<string, mixed> $attributes = [])
 */
final class WriterArticle extends Model
{
    use HasArabicSearchKeys;
    use HasArabicSlug;

    /** @var string */
    protected $table = 'articles';

    /** @var array<int, string> */
    protected $guarded = [];

    protected function arabicSlugSourceColumn(): string
    {
        return 'title';
    }

    protected function arabicSlugTargetColumn(): string
    {
        return 'slug';
    }

    protected function arabicSlugMode(): SlugMode
    {
        return SlugMode::Unicode;
    }

    /**
     * Generate a custom unique slug that prefers title-writer before numeric fallback.
     */
    protected function makeUniqueArabicSlug(string $base, string $column): string
    {
        $writer = $this->getAttribute('writer');

        if (is_scalar($writer) && trim((string) $writer) !== '') {
            $writerSlug = Arabic::slug((string) $writer, SlugMode::Unicode, maxWords: 4);

            if ($writerSlug !== '') {
                $candidate = $base.'-'.$writerSlug;

                if (! $this->arabicSlugExists($column, $candidate)) {
                    return $candidate;
                }
            }
        }

        return $this->makeNumberedArabicSlug($base, $column);
    }

    /**
     * @return array<string, string>
     */
    protected function arabicSearchableColumns(): array
    {
        return [
            'title' => 'title_search',
            'writer' => 'writer_search',
        ];
    }
}
