<?php

declare(strict_types=1);

namespace ArabicSupport\Tests\Unit;

use ArabicSupport\ArabicText;
use ArabicSupport\Enums\SlugMode;
use PHPUnit\Framework\TestCase;

final class ArabicTextTest extends TestCase
{
    public function test_fluent_pipeline(): void
    {
        $slug = ArabicText::make('<b>دَلِيلُ المستخدم</b>')
            ->stripHtml()
            ->stripDiacritics()
            ->slug(SlugMode::Unicode);

        $this->assertSame('دليل-المستخدم', $slug);
    }
}
