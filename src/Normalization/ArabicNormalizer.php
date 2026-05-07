<?php

declare(strict_types=1);

namespace ArabicSupport\Normalization;

use ArabicSupport\Cleaning\UnicodeSecurityCleaner;
use ArabicSupport\Cleaning\WhitespaceNormalizer;
use ArabicSupport\Compat\StringSupport;
use ArabicSupport\Enums\ArabicPolicy;
use ArabicSupport\Enums\HamzaPolicy;
use ArabicSupport\Enums\TaMarbutaPolicy;
use ArabicSupport\Numbers\ArabicDigits;
use Normalizer;

/**
 * Normalizes Arabic text according to the intended use context.
 *
 * Policy intent:
 *
 *   Strict  — "Do not touch this text, only fix encoding."
 *              Use for: byte-level comparison, storing original input.
 *
 *   Display  — "Clean for human eyes, keep spelling identity."
 *              Use for: names, headings, page content, previews.
 *
 *   Slug     — "Make this URL-safe while staying human-readable."
 *              Use for: Unicode slugs, readable URLs, filenames.
 *              Does NOT fold ئ, ى, ة, or ؤ — spelling identity is preserved.
 *
 *   Search   — "Normalize aggressively for matching, not display."
 *              Use for: search indexes, name_search columns, moderation.
 *              Note: ئ is intentionally NOT folded to ي — see normalizeLetters().
 *
 *   Security — Strip invisible/bidi Unicode controls, then apply Display rules.
 */
final class ArabicNormalizer
{
    /**
     * Create an Arabic normalizer with injectable stripping and whitespace helpers.
     */
    public function __construct(
        private readonly DiacriticsStripper $diacritics = new DiacriticsStripper,
        private readonly TatweelStripper $tatweel = new TatweelStripper,
        private readonly WhitespaceNormalizer $whitespace = new WhitespaceNormalizer,
    ) {}

    /**
     * Normalize Arabic text using the selected policy.
     */
    public function normalize(string $text, ArabicPolicy $policy = ArabicPolicy::Display): string
    {
        // Always apply Unicode NFC normalization when intl is available.
        $text = $this->applyNfc($text);

        // Security policy: strip invisible/bidi controls first, then fall through to Display.
        if ($policy === ArabicPolicy::Security) {
            $text = (new UnicodeSecurityCleaner)->clean($text);
        }

        // Strict: minimum possible normalization — NFC only.
        if ($policy === ArabicPolicy::Strict) {
            return $text;
        }

        // All remaining policies strip tatweel and normalize whitespace.
        $text = $this->tatweel->strip($text);
        $text = $this->whitespace->normalize($text);

        // Display / Security: clean for human eyes without changing spelling identity.
        if ($policy === ArabicPolicy::Display || $policy === ArabicPolicy::Security) {
            return $text;
        }

        // Slug, Search: strip diacritics and convert digits to Latin.
        $text = $this->diacritics->strip($text, true);
        $text = (new ArabicDigits)->toLatin($text);

        if ($policy === ArabicPolicy::Slug) {
            // Slug: human-readable URL — preserve spelling identity.
            // No alef folding, no ة→ه, no ى→ي, no ئ→ي, no ؤ→و.
            // Only Persian variants (ک/ی/ۀ/ە) are normalized to their Arabic equivalents.
            $text = $this->normalizeLetters(
                $text,
                hamza: HamzaPolicy::Keep,
                taMarbuta: TaMarbutaPolicy::Keep,
                normalizeAlef: false,
                normalizeAlefMaqsura: false,
                normalizePersianLetters: true,
            );

            return StringSupport::lower($this->whitespace->normalize($text));
        }

        // Search: aggressive normalization for broad matching.
        // ئ is intentionally NOT folded to ي — it carries root-level hamza
        // information (e.g. رئيس vs رييس) that mustn't be silently discarded.
        // Call normalizeLetters(hamza: HamzaPolicy::FoldAll) directly if you
        // need ئ folding for a specific use case.
        $text = $this->normalizeLetters(
            $text,
            hamza: HamzaPolicy::Fold,        // folds ؤ→و; keeps ئ intact
            taMarbuta: TaMarbutaPolicy::Haa,  // ة→ه for broad matching
            normalizeAlef: true,              // أ/إ/آ/ٱ → ا
            normalizeAlefMaqsura: true,       // ى→ي
            normalizePersianLetters: true,
        );

        return StringSupport::lower($this->whitespace->normalize($text));
    }

    /**
     * Normalize common Arabic letter variants with explicit enum policies.
     *
     * Note on ئ (U+0626, ya with hamza above):
     *   HamzaPolicy::Fold  — folds ؤ→و only. ئ is kept as-is.
     *   HamzaPolicy::FoldAll — also folds ئ→ي. Use only when you explicitly
     *     accept the loss of root-level hamza information (e.g. رئيس→رييس).
     *
     * @param  bool  $normalizeAlef  أ/إ/آ/ٱ → ا
     * @param  bool  $normalizeAlefMaqsura  ى → ي
     * @param  bool  $normalizePersianLetters  ک/ی/ۀ/ە → Arabic equivalents
     */
    public function normalizeLetters(
        string $text,
        HamzaPolicy $hamza = HamzaPolicy::Fold,
        TaMarbutaPolicy $taMarbuta = TaMarbutaPolicy::Keep,
        bool $normalizeAlef = true,
        bool $normalizeAlefMaqsura = true,
        bool $normalizePersianLetters = true,
    ): string {
        $map = [];

        if ($normalizePersianLetters) {
            $map += [
                'ک' => 'ك',  // Persian kaf → Arabic kaf
                'ی' => 'ي',  // Persian ya  → Arabic ya
                'ۀ' => 'ة',  // Persian he with hamza → ta marbuta
                'ە' => 'ه',  // Kurdish he  → Arabic ha
            ];
        }

        if ($normalizeAlef) {
            $map += [
                'أ' => 'ا',  // alef with hamza above
                'إ' => 'ا',  // alef with hamza below
                'آ' => 'ا',  // alef with madda
                'ٱ' => 'ا',  // alef wasla
            ];
        }

        if ($normalizeAlefMaqsura) {
            $map['ى'] = 'ي';
        }

        if ($hamza === HamzaPolicy::Fold) {
            // ؤ→و only. ئ is intentionally excluded — see docblock above.
            $map['ؤ'] = 'و';
        }

        if ($hamza === HamzaPolicy::FoldAll) {
            $map['ؤ'] = 'و';
            $map['ئ'] = 'ي';
        }

        $map += match ($taMarbuta) {
            TaMarbutaPolicy::Keep => [],
            TaMarbutaPolicy::Haa => ['ة' => 'ه'],
            TaMarbutaPolicy::Taa => ['ة' => 'ت'],
        };

        return strtr($text, $map);
    }

    /** Create a normalized key suitable for search columns and comparisons. */
    public function searchKey(string $text): string
    {
        return $this->normalize($text, ArabicPolicy::Search);
    }

    /** Apply Unicode NFC normalization when the intl extension is available. */
    private function applyNfc(string $text): string
    {
        if (class_exists(Normalizer::class)) {
            $normalized = Normalizer::normalize($text, Normalizer::FORM_C);
            if (is_string($normalized)) {
                return $normalized;
            }
        }

        return $text;
    }
}
