<?php

declare(strict_types=1);

namespace ArabicSupport\Tests\Unit;

use ArabicSupport\Arabic;
use ArabicSupport\Enums\ArabicPolicy;
use ArabicSupport\Enums\HamzaPolicy;
use ArabicSupport\Enums\SlugMode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Regression tests for ArabicPolicy boundaries.
 *
 * These examples intentionally use real Arabic spelling edge cases that can
 * easily be damaged by over-aggressive normalization.
 */
final class ArabicPolicyMatrixAdvancedTest extends TestCase
{
    /**
     * @return iterable<string, array{0:string, 1:ArabicPolicy, 2:string}>
     */
    public static function normalizationCases(): iterable
    {
        yield 'strict keeps tatweel, tashkeel, multiple spaces' => [
            'مُدَرِّسَــةٌ   عَلَى',
            ArabicPolicy::Strict,
            'مُدَرِّسَــةٌ   عَلَى',
        ];

        yield 'display removes tatweel and trims whitespace but keeps tashkeel and spelling' => [
            '  مُدَرِّسَــةٌ   عَلَى  ',
            ArabicPolicy::Display,
            'مُدَرِّسَةٌ عَلَى',
        ];

        yield 'slug strips tashkeel but preserves readable spelling' => [
            'قائِمةٌ تَجْرِيبيّة على مَنْصّةِ الإدارة',
            ArabicPolicy::Slug,
            'قائمة تجريبية على منصة الإدارة',
        ];

        yield 'search folds for matching but intentionally keeps ئ' => [
            'مسؤول رئيس مسائل مدرسة على',
            ArabicPolicy::Search,
            'مسوول رئيس مسائل مدرسه علي',
        ];

        yield 'security removes unsafe controls without folding spelling' => [
            "إدارة\u{200B}\u{202E} المبيعات",
            ArabicPolicy::Security,
            'إدارة المبيعات',
        ];
    }

    #[DataProvider('normalizationCases')]
    public function test_policy_boundaries_are_explicit(string $input, ArabicPolicy $policy, string $expected): void
    {
        $this->assertSame($expected, Arabic::normalize($input, $policy));
    }

    public function test_unicode_slug_does_not_use_search_folding(): void
    {
        $input = 'قائِمةٌ تَجْرِيبيّة على مَنْصّةِ الإدارة';

        $this->assertSame(
            'قائمة-تجريبية-على-منصة-الإدارة',
            Arabic::slug($input, SlugMode::Unicode, maxWords: 20),
        );

        $this->assertSame(
            'قائمه تجريبيه علي منصه الاداره',
            Arabic::searchKey($input),
        );
    }

    public function test_fold_all_is_explicit_when_ya_hamza_should_be_folded(): void
    {
        $this->assertSame('رئيس مسائل', Arabic::normalizeLetters('رئيس مسائل', HamzaPolicy::Fold));
        $this->assertSame('رييس مسايل', Arabic::normalizeLetters('رئيس مسائل', HamzaPolicy::FoldAll));
    }
}
