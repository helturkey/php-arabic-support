# Direct Classes

Use direct classes when you want explicit dependencies instead of the static API.

```php
use ArabicSupport\Cleaning\TextCleaner;
use ArabicSupport\Normalization\ArabicNormalizer;
use ArabicSupport\Slug\ArabicSlugger;

$clean = (new TextCleaner())->sanitizePlain($input);
$search = (new ArabicNormalizer())->searchKey($input);
$slug = (new ArabicSlugger())->unicode($input);
```

Important direct classes:

- `TextCleaner`
- `WhitespaceNormalizer`
- `UnicodeSecurityCleaner`
- `ArabicNormalizer`
- `DiacriticsStripper`
- `TatweelStripper`
- `ArabicDigits`
- `ArabicSlugger`
- `ArabicTransliterator`
- `ArabicPunctuation`
- `ArabicNameNormalizer`
- `TextExcerpt`
- `ProfanityFilter`
- `ProfanityWordsLoader`
- `ArabicPatterns`
