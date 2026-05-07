# استخدام الأصناف مباشرة

يمكن استخدام الأصناف المتخصصة بدل الواجهة الثابتة عند الحاجة إلى dependencies صريحة.

```php
use ArabicSupport\Cleaning\TextCleaner;
use ArabicSupport\Normalization\ArabicNormalizer;
use ArabicSupport\Slug\ArabicSlugger;

$clean = (new TextCleaner())->sanitizePlain($input);
$search = (new ArabicNormalizer())->searchKey($input);
$slug = (new ArabicSlugger())->unicode($input);
```

أهم الأصناف: `TextCleaner`, `UnicodeSecurityCleaner`, `ArabicNormalizer`, `ArabicDigits`, `ArabicSlugger`, `ArabicPatterns`, `ProfanityWordsLoader`.
