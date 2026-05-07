# وحدات الطول

توفر الحزمة ثلاث وحدات لقياس النص.

## Grapheme

يمثل الحرف المرئي للمستخدم، فيبقي الحرف الأساسي مع تشكيله.

```php
Arabic::length('مُ', LengthUnit::Grapheme); // 1
Arabic::substr('مُحَمَّد', 0, 2, LengthUnit::Grapheme); // مُحَ
```

استخدمه في الواجهة والمختصرات والقص الآمن للعرض.

## Unicode

يقيس نقاط Unicode.

```php
Arabic::length('مُ', LengthUnit::Unicode); // 2
```

## Byte

يقيس بايتات UTF-8.

```php
Arabic::length('مُ', LengthUnit::Byte);
```

## سلوك limit

`limit()` يحافظ على أن الطول النهائي لا يتجاوز الحد المطلوب، بما في ذلك اللاحقة.

```php
Arabic::limit('مُحَمَّد علي', 5, LengthUnit::Grapheme, '...');
// مُحَ...
```
