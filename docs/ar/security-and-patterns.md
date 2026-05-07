# الأمان والأنماط

قد يحتوي النص على محارف مخفية أو محارف تحكم في الاتجاه. هذه المحارف قد تكون صالحة Unicode لكنها خطرة في أسماء المستخدمين والملفات والتعليقات.

```php
use ArabicSupport\Cleaning\UnicodeSecurityCleaner;

$cleaner = new UnicodeSecurityCleaner();

$cleaner->hasBidiControls($text);
$cleaner->hasInvisibleCharacters($text);
$cleaner->clean($text);
```

`ArabicPatterns` هو مصدر regex المركزي:

```php
use ArabicSupport\Patterns\ArabicPatterns;

preg_match(ArabicPatterns::arabic(), $text);
preg_replace(ArabicPatterns::diacritics(), '', $text);
preg_match(ArabicPatterns::slug(), 'دليل-المستخدم');
```
