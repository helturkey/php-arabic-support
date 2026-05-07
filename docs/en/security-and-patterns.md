# Security and Patterns

## Unicode security

Arabic text may contain invisible controls, zero-width marks, and bidirectional controls. These characters may be valid Unicode, but they can be unsafe in usernames, filenames, public comments, and imported text.

```php
use ArabicSupport\Cleaning\UnicodeSecurityCleaner;

$cleaner = new UnicodeSecurityCleaner();

$cleaner->hasBidiControls($text);
$cleaner->hasInvisibleCharacters($text);
$cleaner->clean($text);
```

## ArabicPatterns

`ArabicPatterns` centralizes reusable regex fragments and complete patterns.

```php
use ArabicSupport\Patterns\ArabicPatterns;

preg_match(ArabicPatterns::arabic(), $text);
preg_replace(ArabicPatterns::diacritics(), '', $text);
preg_match(ArabicPatterns::slug(), 'دليل-المستخدم');
```

Keep regex definitions in `ArabicPatterns` and behavior in cleaner/normalizer classes.
