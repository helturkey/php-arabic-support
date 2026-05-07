<?php

declare(strict_types=1);

namespace ArabicSupport\Slug;

use ArabicSupport\Enums\SlugMode;

/**
 * Generates unique slugs using a caller-provided existence callback.
 */
final class UniqueSlugger
{
    public function __construct(
        private readonly ArabicSlugger $slugger = new ArabicSlugger,
    ) {}

    /**
     * Generate a unique slug.
     *
     * The callback receives the candidate slug and must return true when it
     * already exists in storage.
     *
     * @param  callable(string):bool  $exists
     */
    public function unique(string $text, callable $exists, SlugMode $mode = SlugMode::Unicode, string $separator = '-'): string
    {
        $base = $this->slugger->slug($text, $mode, $separator);
        $slug = $base;
        $counter = 2;

        while ($exists($slug)) {
            $slug = $base.$separator.$counter;
            $counter++;
        }

        return $slug;
    }
}
