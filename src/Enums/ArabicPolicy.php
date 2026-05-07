<?php

declare(strict_types=1);

namespace ArabicSupport\Enums;

/**
 * The intended use context for Arabic normalization.
 *
 * Each policy answers a different question about what you need from the text:
 *
 *   Strict   — "Do not touch this text, only fix Unicode normalization."
 *   Display  — "How do I show this to a user cleanly?"
 *   Slug     — "How do I turn this into a human-readable URL?"
 *   Search   — "How do I normalize this for matching regardless of spelling variation?"
 *   Security — "How do I clean unsafe Unicode controls without altering spelling?"
 */
enum ArabicPolicy: string
{
    /**
     * Apply the minimum possible normalization: NFC encoding only.
     *
     * Does: Unicode NFC normalization only when intl is available.
     * Does not: strip tatweel, normalize whitespace, strip diacritics,
     * fold letters, or convert digits.
     * Use for: preserving original input as closely as possible.
     */
    case Strict = 'strict';

    /**
     * Preserve readable display text with light cleanup only.
     *
     * Does: NFC normalization, tatweel removal, whitespace normalization.
     * Does not: strip diacritics, fold hamza, convert ة or ى.
     * Use for: names, headings, page content, previews.
     */
    case Display = 'display';

    /**
     * Create a normalized search key for search indexes and comparisons.
     *
     * Does: everything Display does, plus strips diacritics, converts digits
     * to Latin, folds أ/إ/آ→ا, ى→ي, ؤ→و, ة→ه, normalizes Persian letters.
     * Does not: fold ئ→ي by default; use HamzaPolicy::FoldAll explicitly when needed.
     * Use for: search columns, whereArabic(), moderation matching.
     * This output is not suitable for display.
     */
    case Search = 'search';

    /**
     * Prepare text for generating human-readable Unicode or ASCII slugs.
     *
     * Does: everything Display does, plus strips diacritics, converts digits
     * to Latin, normalizes Persian letter variants (ک/ی/ۀ/ە).
     * Does not: fold alef variants, ى→ي, ة→ه, ؤ→و, or ئ→ي.
     * Use for: Unicode slugs, readable URLs, safe filename bases.
     */
    case Slug = 'slug';

    /**
     * Strip invisible and bidirectional Unicode controls without touching letters.
     *
     * Does: removes bidi controls and zero-width characters, then applies Display.
     * Use for: sanitizing user input before storage, security-sensitive contexts.
     */
    case Security = 'security';
}
