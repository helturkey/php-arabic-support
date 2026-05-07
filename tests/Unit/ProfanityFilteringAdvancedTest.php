<?php

declare(strict_types=1);

namespace ArabicSupport\Tests\Unit;

use ArabicSupport\Arabic;
use ArabicSupport\Filtering\ProfanityWordsLoader;
use PHPUnit\Framework\TestCase;

final class ProfanityFilteringAdvancedTest extends TestCase
{
    public function test_loader_merges_array_txt_json_and_nested_sources_without_duplicates(): void
    {
        $dir = sys_get_temp_dir().'/php-arabic-support-'.bin2hex(random_bytes(4));
        mkdir($dir);

        $txt = $dir.'/blocked.txt';
        $json = $dir.'/blocked.json';
        $nested = $dir.'/nested.txt';

        file_put_contents($txt, "# comment\nممنوع\nمحظور\nممنوع\n");
        file_put_contents($nested, "سيء\nمحظور\n");
        file_put_contents($json, json_encode([
            'words' => ['مرفوض', 'ممنوع'],
            'files' => [$nested, $txt],
        ], JSON_UNESCAPED_UNICODE));

        $words = (new ProfanityWordsLoader)->load([
            'مباشر',
            $txt,
            $json,
            $txt,
            ['مباشر', 'إضافي'],
            $dir.'/missing.txt',
        ]);

        $expected = ['إضافي', 'سيء', 'محظور', 'مباشر', 'مرفوض', 'ممنوع'];
        sort($expected);
        sort($words);

        $this->assertSame($expected, $words);
    }

    public function test_bad_words_match_after_search_normalization_and_respect_word_boundaries(): void
    {
        $this->assertTrue(Arabic::containsBadWords('هذا النص مَمْنُوع هنا', ['ممنوع']));
        $this->assertTrue(Arabic::containsBadWords('هذه مدرسة غير مقبولة', ['مدرسه']));
        $this->assertFalse(Arabic::containsBadWords('هذا نص مسموح', ['ممنوع']));
        $this->assertFalse(Arabic::containsBadWords('هذا قبلالممنوعبعد', ['ممنوع']));
    }
}
