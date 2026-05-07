# فلترة الكلمات المحظورة

لا تحتوي الحزمة على قائمة كلمات محظورة افتراضية، لأن هذه القوائم تختلف حسب المشروع واللهجة والسياق.

## مصدر array

```php
Arabic::containsBadWords($text, ['blocked_one', 'blocked_two']);
```

## مصدر TXT

```php
Arabic::containsBadWords($text, resource_path('profanity/blocked.txt'));
```

## مصدر JSON

```json
{
  "words": ["blocked_one"],
  "files": ["/absolute/path/to/more.txt"]
}
```

## Laravel rule

```php
'comment' => ['nullable', new NoBadWords()],
'comment' => ['nullable', new NoBadWords(['blocked_one'])],
'comment' => ['nullable', new NoBadWords(resource_path('profanity/blocked.txt'))],
```
