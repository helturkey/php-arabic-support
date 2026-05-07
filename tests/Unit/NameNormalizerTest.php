<?php

declare(strict_types=1);

namespace ArabicSupport\Tests\Unit;

use ArabicSupport\Arabic;
use PHPUnit\Framework\TestCase;

final class NameNormalizerTest extends TestCase
{
    public function test_it_applies_configured_name_corrections_by_default(): void
    {
        $this->assertSame('أحمد علي', Arabic::name('احمد علي'));
    }

    public function test_it_keeps_alef_maqsura_by_default_for_display_names(): void
    {
        $this->assertSame('عيسى', Arabic::name('عيسى'));
    }

    public function test_it_can_normalize_alef_maqsura_when_requested(): void
    {
        $this->assertSame('عيسي', Arabic::name('عيسى', normalizeAlefMaqsura: true));
    }
}
