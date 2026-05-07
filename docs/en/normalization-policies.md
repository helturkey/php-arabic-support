# Normalization Policies

Policies describe intent, not just cleaning strength.

## Strict

Minimal normalization. It preserves the input as much as possible. Use it when exact text matters.

## Display

Light cleanup for text shown to users. It normalizes Unicode, removes tatweel, and normalizes whitespace without folding Arabic letters.

## Slug

URL-oriented readable text. It strips diacritics and tatweel, normalizes whitespace and digits, but preserves spelling identity for readable Unicode slugs. It should not fold `ة`, `ى`, `إ`, or `ئ` like search keys.

## Search

Aggressive normalization for search and comparison. It removes marks, folds common letter variants, normalizes digits, and lowercases Latin text. Do not display search keys to users.

## Security

Removes invisible and bidirectional Unicode controls while preserving normal spelling.

```php
use ArabicSupport\Arabic;
use ArabicSupport\Enums\ArabicPolicy;

Arabic::normalize('مُدَرِّسَةٌ عَلَى', ArabicPolicy::Display);
Arabic::normalize('مُدَرِّسَةٌ عَلَى', ArabicPolicy::Search);
Arabic::normalize('قائِمةٌ تَجْرِيبيّة', ArabicPolicy::Slug);
```
