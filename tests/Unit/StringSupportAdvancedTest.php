<?php

declare(strict_types=1);

namespace ArabicSupport\Tests\Unit;

use ArabicSupport\Arabic;
use ArabicSupport\Enums\LengthUnit;
use PHPUnit\Framework\TestCase;

final class StringSupportAdvancedTest extends TestCase
{
    public function test_length_units_are_intentionally_different_for_diacritized_arabic(): void
    {
        $text = 'مُحَمَّد';

        $this->assertSame(4, Arabic::length($text, LengthUnit::Grapheme));
        $this->assertSame(8, Arabic::length($text, LengthUnit::Unicode));
        $this->assertGreaterThan(Arabic::length($text, LengthUnit::Unicode), Arabic::length($text, LengthUnit::Byte));
    }

    public function test_grapheme_substr_keeps_combining_marks_with_base_letters(): void
    {
        $this->assertSame('مُحَ', Arabic::substr('مُحَمَّد', 0, 2, LengthUnit::Grapheme));
    }

    public function test_unicode_substr_can_slice_by_code_points_when_needed(): void
    {
        $this->assertSame('مُ', Arabic::substr('مُحَمَّد', 0, 2, LengthUnit::Unicode));
    }

    public function test_byte_substr_never_returns_invalid_utf8(): void
    {
        $result = Arabic::substr('أحمد', 0, 3, LengthUnit::Byte);

        $this->assertSame(1, preg_match('//u', $result));
    }

    public function test_limit_includes_suffix_inside_final_limit(): void
    {
        $this->assertSame('مُحَ...', Arabic::limit('مُحَمَّد علي', 5, LengthUnit::Grapheme, '...'));
        $this->assertSame('م...', Arabic::limit('مُحَمَّد علي', 4, LengthUnit::Unicode, '...'));
        $this->assertSame('..', Arabic::limit('مُحَمَّد علي', 2, LengthUnit::Grapheme, '...'));
    }

    public function test_short_text_is_returned_without_suffix(): void
    {
        $this->assertSame('مرحبا', Arabic::limit('مرحبا', 10, LengthUnit::Grapheme, '...'));
    }

    public function test_limit_never_returns_broken_utf8_replacement_character(): void
    {
        $this->assertStringNotContainsString(
            '�',
            Arabic::limit('مُحَمَّدٌ في الإدارة', 5, LengthUnit::Grapheme, '...')
        );

        $this->assertStringNotContainsString(
            '�',
            Arabic::limit('محمد في الإدارة', 4, LengthUnit::Unicode, '...')
        );

        $this->assertStringNotContainsString(
            '�',
            Arabic::limit('محمد في الإدارة', 5, LengthUnit::Byte, '...')
        );
    }
}
