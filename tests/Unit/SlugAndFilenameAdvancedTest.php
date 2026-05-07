<?php

declare(strict_types=1);

namespace ArabicSupport\Tests\Unit;

use ArabicSupport\Arabic;
use ArabicSupport\Enums\SlugMode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SlugAndFilenameAdvancedTest extends TestCase
{
    /**
     * @return iterable<string, array{0:string, 1:string}>
     */
    public static function unicodeSlugCases(): iterable
    {
        yield 'article title with hamza and ta marbuta' => [
            'إدارة المنتجات الرقمية في الشركة الناشئة',
            'إدارة-المنتجات-الرقمية-في-الشركة-الناشئة',
        ];

        yield 'mixed product title' => [
            'هاتف iPhone 15 Pro Max — نسخة الشرق الأوسط',
            'هاتف-iphone-15-pro-max-نسخة-الشرق-الأوسط',
        ];

        yield 'Persian letters and digits are normalized for URL safety' => [
            'كتاب فارسی کبير ۱۲۳',
            'كتاب-فارسي-كبير-123',
        ];
    }

    #[DataProvider('unicodeSlugCases')]
    public function test_unicode_slug_real_world_cases(string $input, string $expected): void
    {
        $this->assertSame($expected, Arabic::unicodeSlug($input, maxWords: 20));
    }

    public function test_ascii_slug_is_ascii_only(): void
    {
        $slug = Arabic::slug('إدارة المنتجات الرقمية ٢٠٢٦', SlugMode::Ascii, maxWords: 20);

        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $slug);
        $this->assertStringContainsString('2026', $slug);
    }

    public function test_slug_respects_word_and_length_limits_without_trailing_separator(): void
    {
        $slug = Arabic::unicodeSlug('واحد اثنان ثلاثة أربعة خمسة ستة سبعة', maxWords: 4, maxLength: 20);

        $this->assertLessThanOrEqual(20, Arabic::unicodeLength($slug));
        $this->assertFalse(str_ends_with($slug, '-'));
        $this->assertStringNotContainsString('ستة', $slug);
    }

    public function test_safe_filename_removes_path_separators_and_preserves_extension(): void
    {
        $this->assertSame(
            'تقرير-المبيعات-الربع-الأول-2026.pdf',
            Arabic::safeFilename('تقرير: المبيعات/الربع الأول؟ ٢٠٢٦.pdf'),
        );
    }

    public function test_safe_filename_falls_back_when_base_is_empty(): void
    {
        $this->assertSame('file.pdf', Arabic::safeFilename('!!!.pdf'));
    }
}
