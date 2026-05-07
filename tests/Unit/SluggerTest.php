<?php

declare(strict_types=1);

namespace ArabicSupport\Tests\Unit;

use ArabicSupport\Arabic;
use ArabicSupport\Enums\SlugMode;
use PHPUnit\Framework\TestCase;

final class SluggerTest extends TestCase
{
    public function test_it_creates_unicode_slug(): void
    {
        $this->assertSame('دليل-المستخدم-العربي-2026', Arabic::unicodeSlug('دليل المستخدم العربي 2026'));
    }

    public function test_it_limits_slug_words(): void
    {
        $this->assertSame('واحد-اثنان-ثلاثة', Arabic::unicodeSlug('واحد اثنان ثلاثة أربعة', '-', 3));
    }

    public function test_it_creates_ascii_slug(): void
    {
        $slug = Arabic::slug('دليل المستخدم', SlugMode::Ascii);

        $this->assertNotSame('', $slug);
        $this->assertSame(1, preg_match('/^[a-z0-9\-]+$/', $slug));
    }

    public function test_it_creates_safe_filename(): void
    {
        $this->assertSame('تقرير-المبيعات-العام.pdf', Arabic::safeFilename('تقرير: المبيعات/العام؟.pdf'));
    }

    public function test_to_ascii_accepts_normalization_option(): void
    {
        $normalized = Arabic::toAscii('آثار ١٢٣');
        $closer = Arabic::toAscii('آثار ١٢٣', normalize: false);

        $this->assertNotSame('', $normalized);
        $this->assertNotSame('', $closer);
        $this->assertSame(1, preg_match('/^[\x20-\x7E]+$/', $normalized));
        $this->assertSame(1, preg_match('/^[\x20-\x7E]+$/', $closer));
    }
}
