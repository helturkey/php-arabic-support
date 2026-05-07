<?php

declare(strict_types=1);

namespace ArabicSupport\Tests\Unit;

use ArabicSupport\Arabic;
use PHPUnit\Framework\TestCase;

final class CleaningTest extends TestCase
{
    public function test_it_normalizes_whitespace_inline_by_default(): void
    {
        $this->assertSame('أحمد علي', Arabic::normalizeWhitespace(" أحمد\n\t  علي "));
    }

    public function test_it_can_preserve_new_lines_when_normalizing_whitespace(): void
    {
        $this->assertSame("أحمد\nعلي", Arabic::normalizeWhitespace(" أحمد\n\t  علي ", preserveNewLines: true));
    }

    public function test_it_strips_ordered_list_prefixes(): void
    {
        $text = "1. البند الأول\n٢- البند الثاني\n(۳) البند الثالث";

        $this->assertSame("البند الأول\nالبند الثاني\nالبند الثالث", Arabic::stripOrderedListPrefixes($text));
    }

    public function test_it_creates_excerpt_without_cutting_word(): void
    {
        $this->assertSame('هذا نص عربي ...', Arabic::excerpt('<p>هذا نص عربي طويل جدا</p>', 12));
    }

    public function test_sanitize_is_conservative_by_default(): void
    {
        $this->assertSame('أَحْمَدُ، مَدْرَسَةٌ؟ iPhone X', Arabic::sanitize('<b>أَحْمَدُ، مَدْرَسَةٌ؟ iPhone X</b>'));
    }

    public function test_sanitize_plain_removes_diacritics_without_search_folding(): void
    {
        $this->assertSame('أحمد، مدرسة؟ iPhone X', Arabic::sanitizePlain('<b>أَحْمَدُ، مَدْرَسَةٌ؟ iPhone X</b>'));
    }

    public function test_sanitize_for_search_is_explicitly_aggressive(): void
    {
        $this->assertSame('احمد مدرسه iphone x', Arabic::sanitizeForSearch('<b>أَحْمَدُ، مَدْرَسَةٌ؟ iPhone X</b>'));
    }

    public function test_it_removes_bidi_controls(): void
    {
        $this->assertSame('أحمد', Arabic::removeBidiControls("أحمد\u{202E}"));
    }
}
