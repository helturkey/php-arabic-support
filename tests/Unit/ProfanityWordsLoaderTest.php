<?php

declare(strict_types=1);

namespace ArabicSupport\Tests\Unit;

use ArabicSupport\Filtering\ProfanityWordsLoader;
use PHPUnit\Framework\TestCase;

final class ProfanityWordsLoaderTest extends TestCase
{
    public function test_it_loads_words_from_array_and_removes_duplicates(): void
    {
        $words = (new ProfanityWordsLoader)->load([
            'محظور',
            ' محظور ',
            '',
            null,
            'مرفوض',
        ]);

        $this->assertSame(['محظور', 'مرفوض'], $words);
    }

    public function test_it_loads_words_from_txt_file(): void
    {
        $path = $this->tempFile("# comment\nمحظور\n\nمرفوض\n");

        $words = (new ProfanityWordsLoader)->load($path);

        $this->assertSame(['محظور', 'مرفوض'], $words);
    }

    public function test_it_loads_words_from_json_list(): void
    {
        $path = $this->tempFile(json_encode(['محظور', 'مرفوض'], JSON_THROW_ON_ERROR), 'json');

        $words = (new ProfanityWordsLoader)->load($path);

        $this->assertSame(['محظور', 'مرفوض'], $words);
    }

    public function test_it_loads_words_and_files_from_json_object(): void
    {
        $txt = $this->tempFile("مرفوض\n");
        $json = $this->tempFile(json_encode([
            'words' => ['محظور'],
            'files' => [$txt],
        ], JSON_THROW_ON_ERROR), 'json');

        $words = (new ProfanityWordsLoader)->load($json);

        $this->assertSame(['محظور', 'مرفوض'], $words);
    }

    public function test_it_ignores_duplicate_files(): void
    {
        $txt = $this->tempFile("محظور\n");

        $words = (new ProfanityWordsLoader)->load([$txt, $txt]);

        $this->assertSame(['محظور'], $words);
    }

    private function tempFile(string $contents, string $extension = 'txt'): string
    {
        $path = sys_get_temp_dir().'/php-arabic-support-'.bin2hex(random_bytes(6)).'.'.$extension;
        file_put_contents($path, $contents);

        return $path;
    }
}
