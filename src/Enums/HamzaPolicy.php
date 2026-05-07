<?php

declare(strict_types=1);

namespace ArabicSupport\Enums;

/**
 * Controls how hamza-bearing letter variants are normalized.
 *
 * Context for ئ (U+0626, ya with hamza above):
 *   This character is structurally different from ي (ya without hamza).
 *   In words like رئيس (president) or مسائل (issues), the hamza is part of
 *   the word root. Silently folding ئ→ي loses this information and produces
 *   forms like رييس that may mislead NLP tools or change search precision.
 *
 *   Use Fold for standard search (folds ؤ→و, keeps ئ intact).
 *   Use FoldAll only when you explicitly accept these tradeoffs.
 */
enum HamzaPolicy: string
{
    /** Keep all hamza-bearing letter variants unchanged. */
    case Keep = 'keep';

    /**
     * Fold ؤ→و for broad matching. Keeps ئ unchanged.
     *
     * This is the default for Search normalization. It improves recall for
     * ؤ variants (e.g. مؤمن/مومن) without discarding ئ root information.
     */
    case Fold = 'fold';

    /**
     * Fold both ؤ→و and ئ→ي.
     *
     * Use only when you explicitly need maximum recall at the cost of
     * precision. Be aware that رئيس and رييس will become identical under
     * this policy, which may be undesirable for many Arabic texts.
     */
    case FoldAll = 'fold_all';
}
