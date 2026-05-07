# Laravel Integration

Laravel integration is optional.

## Facade

```php
use ArabicSupport\Laravel\Facades\Arabic;

Arabic::unicodeSlug('دليل المستخدم');
Arabic::searchKey('إدارة المبيعات');
```

## Configuration

```bash
php artisan vendor:publish --tag=php-arabic-support-config
```

## Str macros

```php
use Illuminate\Support\Str;

Str::arabicSlug('دليل المستخدم');
Str::arabicSearchKey('إدارة المبيعات');
Str::stripArabicDiacritics('مُحَمَّد');
Str::arabicExcerpt('<p>وصف منتج طويل</p>', 100);
```

## HasArabicSlug

```php
use ArabicSupport\Enums\SlugMode;
use ArabicSupport\Laravel\Concerns\HasArabicSlug;
use Illuminate\Database\Eloquent\Model;

final class Product extends Model
{
    use HasArabicSlug;

    protected function arabicSlugSourceColumn(): string
    {
        return 'name';
    }

    protected function arabicSlugTargetColumn(): string
    {
        return 'slug';
    }

    protected function arabicSlugMode(): SlugMode
    {
        return SlugMode::Unicode;
    }
}
```

### Custom unique slug behavior

Override `makeUniqueArabicSlug()` or return a closure from `arabicSlugUniqueResolver()`. The example below appends the writer slug before falling back to the default numbered strategy.

```php
use ArabicSupport\Arabic;
use Closure;
use Illuminate\Database\Eloquent\Model;

protected function arabicSlugUniqueResolver(): ?Closure
{
    return function (string $base, string $column, Model $model): ?string {
        $writer = $model->getAttribute('writer');

        if (! is_scalar($writer) || trim((string) $writer) === '') {
            return null;
        }

        $candidate = $base.'-'.Arabic::slug((string) $writer);

        return $this->arabicSlugExists($column, $candidate)
            ? $this->makeNumberedArabicSlug($base, $column)
            : $candidate;
    };
}
```

## HasArabicSearchKeys

```php
use ArabicSupport\Laravel\Concerns\HasArabicSearchKeys;
use Illuminate\Database\Eloquent\Model;

final class Customer extends Model
{
    use HasArabicSearchKeys;

    /** @return array<string, string> */
    protected function arabicSearchableColumns(): array
    {
        return [
            'name' => 'name_search',
            'company' => 'company_search',
        ];
    }
}
```

Query scopes:

```php
Customer::query()->whereArabic('name_search', 'احمد علي')->first();
Customer::query()->whereArabicContains('company_search', 'المبيعات')->get();
```

## Validation rules

```php
use ArabicSupport\Laravel\Rules\ArabicName;
use ArabicSupport\Laravel\Rules\ArabicSlug;
use ArabicSupport\Laravel\Rules\ArabicText;
use ArabicSupport\Laravel\Rules\ContainsArabic;
use ArabicSupport\Laravel\Rules\NoBadWords;
use ArabicSupport\Laravel\Rules\NoBidiControls;
use ArabicSupport\Laravel\Rules\NoInvisibleCharacters;

$request->validate([
    'title' => ['required', new ArabicText(minRatio: 0.6)],
    'name' => ['nullable', new ArabicName()],
    'slug' => ['nullable', new ArabicSlug()],
    'summary' => ['nullable', new ContainsArabic()],
    'comment' => ['nullable', new NoBadWords()],
    'username' => ['required', new NoBidiControls(), new NoInvisibleCharacters()],
]);
```
