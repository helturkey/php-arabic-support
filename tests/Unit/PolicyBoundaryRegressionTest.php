<?php

declare(strict_types=1);

namespace ArabicSupport\Tests\Unit;

use ArabicSupport\Arabic;
use ArabicSupport\Enums\ArabicPolicy;
use ArabicSupport\Enums\HamzaPolicy;
use ArabicSupport\Enums\SlugMode;
use PHPUnit\Framework\TestCase;

/**
 * Regression tests for policy boundaries.
 *
 * These examples are intentionally realistic and sometimes awkward. They are
 * designed to protect the API from drifting into search-like normalization in
 * places where readable Arabic spelling should be preserved.
 */
final class PolicyBoundaryRegressionTest extends TestCase
{
    public function test_unicode_slug_preserves_readable_arabic_spelling(): void
    {
        $input = 'قائِمةٌ تَجْرِيبيّة على مَنْصّةِ الإدارة';

        $this->assertSame(
            'قائمة-تجريبية-على-منصة-الإدارة',
            Arabic::unicodeSlug($input),
        );
    }

    public function test_search_key_is_intentionally_not_display_text(): void
    {
        $input = 'قائِمةٌ تَجْرِيبيّة على مَنْصّةِ الإدارة';

        $this->assertSame(
            'قائمه تجريبيه علي منصه الاداره',
            Arabic::searchKey($input),
        );
    }

    public function test_slug_policy_is_not_search_policy(): void
    {
        $input = 'قائِمةٌ على مَنْصّةِ الإدارة';

        $this->assertSame('قائمة على منصة الإدارة', Arabic::normalize($input, ArabicPolicy::Slug));
        $this->assertSame('قائمه علي منصه الاداره', Arabic::normalize($input, ArabicPolicy::Search));
    }

    public function test_strict_policy_keeps_whitespace_tatweel_and_diacritics(): void
    {
        $input = "مُدَرِّسَةٌ\t  عــلى";

        $this->assertSame($input, Arabic::normalize($input, ArabicPolicy::Strict));
    }

    public function test_display_policy_cleans_spacing_but_does_not_fold_letters(): void
    {
        $input = "إدارة\t\tمدرَسةٌ   على";

        $this->assertSame('إدارة مدرَسةٌ على', Arabic::normalize($input, ArabicPolicy::Display));
    }

    public function test_fold_all_hamza_is_explicit_not_implicit_search_behavior(): void
    {
        $this->assertSame('رئيس', Arabic::searchKey('رئيس'));
        $this->assertSame('رييس', Arabic::normalizeLetters('رئيس', hamza: HamzaPolicy::FoldAll));
    }

    public function test_slug_mode_enum_routes_to_unicode_and_ascii_modes(): void
    {
        $input = 'إدارة المنتجات ٢٠٢٦';

        $this->assertSame('إدارة-المنتجات-2026', Arabic::slug($input, SlugMode::Unicode));
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', Arabic::slug($input, SlugMode::Ascii));
    }
}
