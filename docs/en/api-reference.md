# API Reference

This page lists the public methods provided by PHP Arabic Support. Each method is described in one line.

## `ArabicSupport\Arabic`

### Factory and fluent API

- `text(string $text): ArabicText` — Creates a fluent `ArabicText` pipeline around the given text.

### Cleaning and sanitization

- `clean(string $text): string` — Removes HTML, unsafe Unicode controls, and excessive whitespace while preserving readable text.
- `sanitize(string $text, ?ArabicPolicy $policy = null, bool $stripDiacritics = false, bool $stripTatweel = true, bool $lowercase = false, bool $keepPunctuation = true): string` — Sanitizes text with explicit options and no forced search normalization.
- `sanitizePlain(string $text, bool $keepPunctuation = true): string` — Removes HTML, tatweel, and diacritics while preserving readable spelling.
- `sanitizeForSearch(string $text): string` — Sanitizes and normalizes text aggressively for search/comparison.
- `stripHtml(string $text): string` — Removes HTML tags and decodes HTML entities.
- `normalizeWhitespace(string $text, bool $preserveNewLines = false): string` — Normalizes whitespace and optionally preserves line breaks.
- `normalizeInlineWhitespace(string $text): string` — Collapses all whitespace into single inline spaces.
- `deepTrim(string $text): string` — Trims regular and invisible Unicode whitespace from both ends.

### Normalization

- `normalize(string $text, ArabicPolicy $policy = ArabicPolicy::Display): string` — Normalizes text using an explicit policy: Strict, Display, Slug, Search, or Security.
- `searchKey(string $text): string` — Builds an aggressive normalized key suitable for search columns and comparisons.
- `normalizeLetters(string $text, HamzaPolicy $hamza = HamzaPolicy::Fold, TaMarbutaPolicy $taMarbuta = TaMarbutaPolicy::Keep, bool $normalizeAlef = true, bool $normalizeAlefMaqsura = true, bool $normalizePersianLetters = true): string` — Normalizes Arabic letter variants with full control over each transformation.
- `stripDiacritics(string $text, bool $includeQuranMarks = true): string` — Removes Arabic diacritics and optionally Quranic marks.
- `stripTatweel(string $text): string` — Removes Arabic tatweel/kashida characters.
- `stripeTatweel(string $text): string` — Backward-compatible alias for `stripTatweel()`.

### Slugs and ASCII

- `slug(string $text, SlugMode $mode = SlugMode::Unicode, string $separator = '-', int $maxWords = 8, int $maxLength = 180): string` — Generates a Unicode or ASCII slug according to the selected mode.
- `unicodeSlug(string $text, string $separator = '-', int $maxWords = 8, int $maxLength = 180): string` — Generates a readable Arabic Unicode slug that preserves spelling identity.
- `asciiSlug(string $text, string $separator = '-', int $maxWords = 8, int $maxLength = 180): string` — Generates an ASCII-only slug using Arabic transliteration.
- `toAscii(string $text, bool $normalize = true): string` — Converts Arabic text to a Latin/ASCII approximation.

### Digits

- `digitsToLatin(string $text): string` — Converts Arabic-Indic and Eastern Arabic/Persian digits to Latin digits.
- `digitsToArabicIndic(string $text): string` — Converts all supported digits to Arabic-Indic digits.
- `digitsToEasternArabic(string $text): string` — Converts all supported digits to Eastern Arabic/Persian digits.
- `normalizeDigits(string $text, DigitSet $target = DigitSet::Latin): string` — Converts digits to the selected `DigitSet`.

### Lists, filenames, names, and punctuation

- `stripOrderedListPrefixes(string $text): string` — Removes ordered-list prefixes from the beginning of each line.
- `safeFilename(string $filename, string $separator = '-'): string` — Creates a filesystem-friendly filename while preserving readable Arabic.
- `name(string $name, int $maxWords = 8, bool $applyCorrections = true, bool $normalizeAlefMaqsura = false): string` — Normalizes a general Arabic display name conservatively.
- `fixPunctuation(string $text): string` — Normalizes Arabic and Latin punctuation spacing.
- `normalizeConjunctionWaw(string $text): string` — Normalizes standalone conjunction waw spacing, such as `و أحمد` to `وأحمد`.

### Length, substring, limit, and excerpts

- `length(string $text, LengthUnit $unit = LengthUnit::Grapheme): int` — Returns text length using grapheme, Unicode-code-point, or byte measurement.
- `graphemeLength(string $text): int` — Returns user-visible character length.
- `unicodeLength(string $text): int` — Returns Unicode code-point length.
- `byteLength(string $text): int` — Returns UTF-8 byte length.
- `substr(string $text, int $start, ?int $length = null, LengthUnit $unit = LengthUnit::Grapheme): string` — Extracts a substring using the selected length unit.
- `limit(string $text, int $limit, LengthUnit $unit = LengthUnit::Grapheme, string $end = '...'): string` — Limits text to a maximum final length, including the suffix.
- `excerpt(string $htmlOrText, int $limit = 200, string $end = ' ...'): string` — Creates a clean excerpt without cutting words in half.

### Security and inspection

- `removeInvisible(string $text): string` — Removes zero-width, bidi-control, and unsafe control characters.
- `removeBidiControls(string $text): string` — Removes bidirectional Unicode control characters.
- `securityClean(string $text): string` — Removes invisible/security-sensitive Unicode characters.
- `containsArabic(string $text): bool` — Checks whether the text contains Arabic script.
- `isArabic(string $text): bool` — Checks whether all letters in the text are Arabic letters.
- `arabicRatio(string $text): float` — Returns the ratio of Arabic letters among all letters.
- `inspect(string $text): array` — Returns diagnostics about Arabic ratio, marks, digits, HTML, and suspicious Unicode.
- `containsBadWords(string $text, array|string|null $words = null): bool` — Checks text against blocked words from an array, TXT file, JSON file, or mixed source.

## `ArabicSupport\ArabicText`

- `make(string $text): self` — Creates a fluent text pipeline.
- `value(): string` — Returns the current pipeline value.
- `__toString(): string` — Casts the pipeline to its current string value.
- `stripHtml(): self` — Removes HTML from the current value.
- `clean(): self` — Applies safe cleaning to the current value.
- `sanitize(...): self` — Sanitizes the current value with explicit options.
- `sanitizePlain(bool $keepPunctuation = true): self` — Applies readable plain-text sanitization.
- `sanitizeForSearch(): self` — Applies search-oriented sanitization.
- `normalizeWhitespace(bool $preserveNewLines = false): self` — Normalizes whitespace in the current value.
- `normalizeInlineWhitespace(): self` — Collapses whitespace into one-line spacing.
- `stripDiacritics(bool $includeQuranMarks = true): self` — Removes Arabic diacritics from the current value.
- `stripTatweel(): self` — Removes tatweel from the current value.
- `normalize(ArabicPolicy $policy = ArabicPolicy::Display): self` — Applies a normalization policy to the current value.
- `searchKey(): string` — Returns a search key from the current value.
- `stripOrderedListPrefixes(): self` — Removes ordered-list prefixes from the current value.
- `fixPunctuation(): self` — Normalizes punctuation spacing in the current value.
- `securityClean(): self` — Removes security-sensitive Unicode characters from the current value.
- `slug(...): string` — Returns a slug from the current value.
- `unicodeSlug(...): string` — Returns a readable Unicode slug from the current value.
- `asciiSlug(...): string` — Returns an ASCII slug from the current value.
- `excerpt(int $limit = 200, string $end = ' ...'): string` — Returns an excerpt from the current value.
- `limit(int $length, LengthUnit $unit = LengthUnit::Grapheme, string $end = '...'): self` — Limits the current value while keeping it chainable.

## Focused classes

### Cleaning

- `OrderedListPrefixStripper::strip(string $text): string` — Removes ordered-list prefixes from each line.
- `TextCleaner::stripHtml(string $text, bool $preserveBlockSpaces = true): string` — Removes HTML while preserving readable word boundaries.
- `TextCleaner::keepTextCharacters(string $text, string $extra = '', bool $keepPunctuation = true, string $replacement = ''): string` — Keeps language characters, numbers, spaces, and selected punctuation.
- `TextCleaner::keepSlugCharacters(string $text): string` — Keeps characters suitable before slug generation.
- `TextCleaner::clean(string $text): string` — Cleans HTML, invisible controls, and whitespace.
- `TextCleaner::sanitize(...): string` — Sanitizes text with configurable options.
- `TextCleaner::sanitizePlain(string $text, bool $keepPunctuation = true): string` — Produces readable plain text without diacritics/tatweel.
- `TextCleaner::sanitizeForSearch(string $text): string` — Produces sanitized search text.
- `TextCleaner::normalizeWhitespace(string $text, bool $preserveNewLines = false): string` — Normalizes whitespace.
- `TextCleaner::normalizeInlineWhitespace(string $text): string` — Normalizes whitespace to one line.
- `TextCleaner::deepTrim(string $text, bool $preserveNewLines = false): string` — Trims Unicode whitespace and controls.
- `UnicodeSecurityCleaner::removeBidiControls(string $text): string` — Removes bidi controls.
- `UnicodeSecurityCleaner::removeZeroWidthCharacters(string $text): string` — Removes zero-width characters.
- `UnicodeSecurityCleaner::removeControlCharacters(string $text, bool $keepNewLines = true): string` — Removes control characters with optional newline preservation.
- `UnicodeSecurityCleaner::removeInvisibleCharacters(string $text, bool $keepNewLines = true): string` — Removes invisible/security-sensitive characters.
- `UnicodeSecurityCleaner::clean(string $text, bool $keepNewLines = true): string` — Alias for removing invisible/security-sensitive characters.
- `UnicodeSecurityCleaner::hasBidiControls(string $text): bool` — Detects bidi controls.
- `UnicodeSecurityCleaner::hasZeroWidthCharacters(string $text): bool` — Detects zero-width characters.
- `UnicodeSecurityCleaner::hasControlCharacters(string $text, bool $keepNewLines = true): bool` — Detects control characters.
- `UnicodeSecurityCleaner::hasInvisibleCharacters(string $text, bool $keepNewLines = true): bool` — Detects invisible/security-sensitive characters.
- `UnicodeSecurityCleaner::hasSuspiciousUnicode(string $text, bool $keepNewLines = true): bool` — Detects suspicious Unicode controls.
- `WhitespaceNormalizer::normalize(string $text, bool $preserveNewLines = false): string` — Normalizes spaces and optionally preserves line breaks.
- `WhitespaceNormalizer::normalizeInline(string $text): string` — Collapses all whitespace into inline spacing.
- `WhitespaceNormalizer::deepTrim(string $text, bool $preserveNewLines = false): string` — Trims Unicode separators and controls from text ends.

### Normalization, digits, and names

- `ArabicNormalizer::normalize(string $text, ArabicPolicy $policy = ArabicPolicy::Display): string` — Applies a normalization policy.
- `ArabicNormalizer::normalizeLetters(...): string` — Applies explicit Arabic letter transformations.
- `ArabicNormalizer::searchKey(string $text): string` — Builds a search key.
- `DiacriticsStripper::strip(string $text, bool $includeQuranMarks = true): string` — Removes Arabic marks.
- `DiacriticsStripper::has(string $text): bool` — Detects Arabic marks.
- `TatweelStripper::strip(string $text): string` — Removes tatweel.
- `TatweelStripper::has(string $text): bool` — Detects tatweel.
- `ArabicDigits::toLatin(string $text): string` — Converts supported digits to Latin.
- `ArabicDigits::toArabicIndic(string $text): string` — Converts supported digits to Arabic-Indic.
- `ArabicDigits::toEasternArabic(string $text): string` — Converts supported digits to Eastern Arabic/Persian.
- `ArabicDigits::normalize(string $text, DigitSet $target = DigitSet::Latin): string` — Converts digits to the selected set.
- `ArabicDigits::hasArabicDigits(string $text): bool` — Detects Arabic-Indic or Eastern Arabic/Persian digits.
- `ArabicNameNormalizer::normalize(...): string` — Normalizes a display name conservatively.

### Slugs, excerpts, punctuation, filtering, and patterns

- `ArabicSlugger::slug(...): string` — Generates a slug using `SlugMode`.
- `ArabicSlugger::unicode(...): string` — Generates a readable Unicode slug.
- `ArabicSlugger::ascii(...): string` — Generates an ASCII slug.
- `ArabicTransliterator::toAscii(string $text, bool $normalize = true): string` — Transliterates Arabic text to ASCII.
- `UniqueSlugger::unique(string $text, callable $exists, SlugMode $mode = SlugMode::Unicode, string $separator = '-'): string` — Generates a unique slug using an existence callback.
- `TextExcerpt::excerpt(string $htmlOrText, int $limit = 200, string $end = ' ...'): string` — Builds an excerpt without cutting words.
- `ArabicPunctuation::addSpaceAfterPunctuation(string $text): string` — Adds missing spaces after punctuation.
- `ArabicPunctuation::normalize(string $text): string` — Normalizes punctuation and spacing.
- `ArabicPunctuation::normalizeConjunctionWaw(string $text): string` — Normalizes standalone conjunction waw.
- `ProfanityFilter::contains(string $text): bool` — Checks text against configured blocked words.
- `ProfanityWordsLoader::load(array|string|null $source): array` — Loads blocked words from arrays, TXT files, JSON files, or mixed sources.
- `ArabicPatterns::charClass(string $fragment): string` — Wraps a regex fragment in a character class.
- `ArabicPatterns::orderedListPrefix(): string` — Returns the ordered-list-prefix regex.
- `ArabicPatterns::arabicName(): string` — Returns the Arabic name validation regex.
- `ArabicPatterns::arabic(): string` — Returns the Arabic-script detection regex.
- `ArabicPatterns::diacritics(bool $includeQuranMarks = true): string` — Returns the Arabic diacritics regex.
- `ArabicPatterns::tatweel(): string` — Returns the tatweel regex.
- `ArabicPatterns::bidiControls(): string` — Returns the bidi-control regex.
- `ArabicPatterns::zeroWidth(): string` — Returns the zero-width-character regex.
- `ArabicPatterns::slugAllowed(): string` — Returns a regex for characters not allowed before slugging.
- `ArabicPatterns::slug(string $separator = '-'): string` — Returns the slug validation regex.
- `ArabicPatterns::controlExceptNewLines(): string` — Returns a control-character regex that preserves new lines.
- `ArabicPatterns::allControlCharacters(): string` — Returns the full Unicode control-character regex.
- `ArabicPatterns::invisibleCharacters(): string` — Returns the combined invisible/security-sensitive regex.
