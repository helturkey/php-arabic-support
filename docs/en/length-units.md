# Length Units

The package supports three length units.

## Grapheme

User-visible character units. This keeps a base letter and combining marks together.

```php
Arabic::length('مُ', LengthUnit::Grapheme); // 1
Arabic::substr('مُحَمَّد', 0, 2, LengthUnit::Grapheme); // مُحَ
```

Use it for UI labels, excerpts, and display-safe truncation.

## Unicode

Unicode code-point units.

```php
Arabic::length('مُ', LengthUnit::Unicode); // 2
```

Use it when your limit is based on Unicode characters rather than visible characters.

## Byte

UTF-8 byte length.

```php
Arabic::length('مُ', LengthUnit::Byte);
```

Use it for byte-limited protocols, indexes, or storage constraints.

## Limit behavior

`limit()` keeps the final output length within the requested limit, including the suffix.

```php
Arabic::limit('مُحَمَّد علي', 5, LengthUnit::Grapheme, '...');
// مُحَ...
```
