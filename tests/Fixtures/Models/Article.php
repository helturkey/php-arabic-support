<?php

declare(strict_types=1);

namespace ArabicSupport\Tests\Fixtures\Models;

use ArabicSupport\Enums\SlugMode;
use ArabicSupport\Laravel\Concerns\HasArabicSearchKeys;
use ArabicSupport\Laravel\Concerns\HasArabicSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Test model used by the Laravel/Eloquent integration tests.
 *
 * @property int $id
 * @property string $title
 * @property string|null $title_search
 * @property string|null $body
 * @property string|null $body_search
 * @property string|null $slug
 *
 * @method static Builder query()
 * @method static self create(array<string, mixed> $attributes = [])
 */
final class Article extends Model
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
     * @return array<string, string>
     */
    protected function arabicSearchableColumns(): array
    {
        return [
            'title' => 'title_search',
            'body' => 'body_search',
        ];
    }
}
