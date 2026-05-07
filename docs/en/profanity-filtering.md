# Profanity Filtering

The package does not ship with a predefined blocked-word list. Moderation rules are project-specific and dialect-sensitive.

## Array source

```php
Arabic::containsBadWords($text, ['blocked_one', 'blocked_two']);
```

## TXT source

```php
Arabic::containsBadWords($text, resource_path('profanity/blocked.txt'));
```

TXT format:

```txt
# comments are ignored
blocked_one
blocked_two
```

## JSON source

```json
["blocked_one", "blocked_two"]
```

or:

```json
{
  "words": ["blocked_one"],
  "files": ["/absolute/path/to/more.txt"]
}
```

## Laravel rule

```php
use ArabicSupport\Laravel\Rules\NoBadWords;

'comment' => ['nullable', new NoBadWords()],
'comment' => ['nullable', new NoBadWords(['blocked_one'])],
'comment' => ['nullable', new NoBadWords(resource_path('profanity/blocked.txt'))],
```

When no constructor source is provided, the rule reads `php-arabic-support.profanity.words` from config.
