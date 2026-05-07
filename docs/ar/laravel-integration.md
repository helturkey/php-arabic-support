# تكامل Laravel

التكامل اختياري ولا يؤثر في مستخدم PHP المباشر.

## Facade

```php
use ArabicSupport\Laravel\Facades\Arabic;

Arabic::unicodeSlug('دليل المستخدم');
Arabic::searchKey('إدارة المبيعات');
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

### تخصيص slug الفريد

يمكن تجاوز `makeUniqueArabicSlug()` أو إرجاع closure من `arabicSlugUniqueResolver()`. المثال التالي يضيف اسم الكاتب إلى الرابط أولًا، ثم يعود إلى الترقيم الافتراضي إذا كان الرابط المقترح مستخدمًا.

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

protected function arabicSearchableColumns(): array
{
    return [
        'name' => 'name_search',
        'company' => 'company_search',
    ];
}
```

## قواعد التحقق

```php
$request->validate([
    'title' => ['required', new ArabicText(minRatio: 0.6)],
    'name' => ['nullable', new ArabicName()],
    'slug' => ['nullable', new ArabicSlug()],
    'comment' => ['nullable', new NoBadWords()],
]);
```
