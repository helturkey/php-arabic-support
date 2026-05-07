# أمثلة

## رابط منتج

```php
Arabic::unicodeSlug('هاتف ذكي بإصدار خاص 2026');
// هاتف-ذكي-بإصدار-خاص-2026
```

## عمود بحث

```php
$model->name_search = Arabic::searchKey($model->name);
```

## اسم ملف آمن

```php
Arabic::safeFilename('تقرير: المبيعات/العام؟.pdf');
// تقرير-المبيعات-العام.pdf
```

## حذف ترقيم القوائم

```php
Arabic::stripOrderedListPrefixes("1. إنشاء حساب\n٢- تأكيد البريد\n(۳) إكمال الملف");
```
