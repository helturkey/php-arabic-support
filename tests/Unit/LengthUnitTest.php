<?php

declare(strict_types=1);

namespace ArabicSupport\Tests\Unit;

use ArabicSupport\Arabic;
use ArabicSupport\ArabicText;
use ArabicSupport\Enums\LengthUnit;
use PHPUnit\Framework\TestCase;

final class LengthUnitTest extends TestCase
{
    public function test_it_counts_grapheme_unicode_and_byte_lengths(): void
    {
        $text = 'مُ';

        $this->assertSame(1, Arabic::length($text, LengthUnit::Grapheme));
        $this->assertSame(2, Arabic::length($text, LengthUnit::Unicode));
        $this->assertSame(4, Arabic::length($text, LengthUnit::Byte));
    }

    public function test_it_limits_by_grapheme_by_default(): void
    {
        $this->assertSame('مُ...', Arabic::limit('مُحَمَّد علي', 4));
    }

    public function test_it_limits_by_unicode_code_points(): void
    {
        $this->assertSame('م...', Arabic::limit('مُحَمَّد علي', 4, LengthUnit::Unicode));
    }

    public function test_it_limits_by_utf8_bytes_without_invalid_output(): void
    {
        $limited = Arabic::limit('أحمد', 5, LengthUnit::Byte);

        $this->assertSame('أ...', $limited);
        $this->assertSame(1, preg_match('//u', $limited));
        $this->assertSame(5, Arabic::length($limited, LengthUnit::Byte));
    }

    public function test_fluent_text_limit_accepts_length_unit(): void
    {
        $text = ArabicText::make('مُحَمَّد علي')->limit(4, LengthUnit::Unicode)->value();

        $this->assertSame('م...', $text);
    }
}
