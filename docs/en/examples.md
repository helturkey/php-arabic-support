# Examples

## Product URL

```php
Arabic::unicodeSlug('هاتف ذكي بإصدار خاص 2026');
// هاتف-ذكي-بإصدار-خاص-2026
```

## Search column

```php
$model->name_search = Arabic::searchKey($model->name);
```

## Safe filename

```php
Arabic::safeFilename('تقرير: المبيعات/العام؟.pdf');
// تقرير-المبيعات-العام.pdf
```

## Ordered-list cleanup

```php
Arabic::stripOrderedListPrefixes("1. إنشاء حساب\n٢- تأكيد البريد\n(۳) إكمال الملف");
```

## Display-safe limit

```php
Arabic::limit('مُحَمَّد علي', 5, LengthUnit::Grapheme, '...');
// مُحَ...
```
