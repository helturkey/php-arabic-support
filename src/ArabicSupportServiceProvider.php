<?php

declare(strict_types=1);

namespace ArabicSupport;

use ArabicSupport\Enums\SlugMode;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

/**
 * Laravel service provider for optional framework integration.
 */
final class ArabicSupportServiceProvider extends ServiceProvider
{
    /** Register services in the Laravel container. */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/php-arabic-support.php', 'php-arabic-support');

        $this->app->singleton('php-arabic', static fn (): ArabicManager => new ArabicManager);
    }

    /** Publish configuration and register Laravel macros. */
    public function boot(): void
    {
        if (function_exists('config_path')) {
            $this->publishes([
                __DIR__.'/../config/php-arabic-support.php' => \config_path('php-arabic-support.php'),
            ], 'php-arabic-support-config');
        }

        $this->registerMacros();
    }

    /**
     * Register Illuminate\Support\Str macros when Laravel is available.
     */
    private function registerMacros(): void
    {
        if (! class_exists(Str::class)) {
            return;
        }

        if (! Str::hasMacro('arabicSlug')) {
            Str::macro('arabicSlug', fn (string $text, SlugMode $mode = SlugMode::Unicode, string $separator = '-'): string => Arabic::slug($text, $mode, $separator));
        }

        if (! Str::hasMacro('arabicSearchKey')) {
            Str::macro('arabicSearchKey', fn (string $text): string => Arabic::searchKey($text));
        }

        if (! Str::hasMacro('stripArabicDiacritics')) {
            Str::macro('stripArabicDiacritics', fn (string $text): string => Arabic::stripDiacritics($text));
        }

        if (! Str::hasMacro('arabicExcerpt')) {
            Str::macro('arabicExcerpt', fn (string $text, int $limit = 200, string $end = ' ...'): string => Arabic::excerpt($text, $limit, $end));
        }
    }
}
