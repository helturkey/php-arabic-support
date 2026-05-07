<?php

declare(strict_types=1);

namespace ArabicSupport\Enums;

/**
 * Controls how ta marbuta "ة" is normalized.
 */
enum TaMarbutaPolicy: string
{
    /** Keep "ة" unchanged. */
    case Keep = 'keep';

    /** Fold "ة" to "ه" for broad Arabic search matching. */
    case Haa = 'haa';

    /** Fold "ة" to "ت" for specific linguistic workflows. */
    case Taa = 'taa';
}
