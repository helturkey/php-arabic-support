<?php

declare(strict_types=1);

namespace ArabicSupport\Filtering;

/**
 * Loads profanity/blocked words from arrays, TXT files, JSON files,
 * or mixed nested sources.
 *
 * Supported examples:
 *
 * - ['word_one', 'word_two']
 * - '/path/to/blocked-words.txt'
 * - '/path/to/blocked-words.json'
 * - ['word_one', '/path/to/blocked-words.txt']
 * - ['words' => ['word_one'], 'files' => ['/path/to/blocked-words.json']]
 *
 * TXT files:
 * - One word per line
 * - Empty lines are ignored
 * - Lines starting with "#" are ignored
 *
 * JSON files:
 * - ["word_one", "word_two"]
 * - {"words": ["word_one"], "files": ["/path/to/more.txt"]}
 */
final class ProfanityWordsLoader
{
    /**
     * Load blocked words from one or more sources.
     *
     * @param  array<array-key, mixed>|string|null  $source
     * @return list<string>
     */
    public function load(array|string|null $source): array
    {
        $loadedFiles = [];

        return $this->normalizeWords(
            $this->loadSource($source, $loadedFiles)
        );
    }

    /**
     * Recursively load words from a source.
     *
     * @param  array<string, true>  $loadedFiles
     * @return list<string>
     */
    private function loadSource(mixed $source, array &$loadedFiles): array
    {
        if ($source === null) {
            return [];
        }

        if (is_array($source)) {
            return $this->loadArray($source, $loadedFiles);
        }

        if (! is_string($source)) {
            return [];
        }

        $source = trim($source);

        if ($source === '') {
            return [];
        }

        if (is_file($source)) {
            return $this->loadFile($source, $loadedFiles);
        }

        if ($this->looksLikeFilePath($source)) {
            return [];
        }

        return [$source];
    }

    /**
     * Load words from an array.
     *
     * Array values may be words, file paths, or nested arrays.
     *
     * @param  array<array-key, mixed>  $source
     * @param  array<string, true>  $loadedFiles
     * @return list<string>
     */
    private function loadArray(array $source, array &$loadedFiles): array
    {
        $words = [];

        foreach ($source as $value) {
            foreach ($this->loadSource($value, $loadedFiles) as $word) {
                $words[] = $word;
            }
        }

        return $words;
    }

    /**
     * Load words from a TXT or JSON file.
     *
     * Duplicate files are ignored using realpath().
     *
     * @param  array<string, true>  $loadedFiles
     * @return list<string>
     */
    private function loadFile(string $path, array &$loadedFiles): array
    {
        $realPath = realpath($path);

        if ($realPath === false || ! is_file($realPath)) {
            return [];
        }

        if (isset($loadedFiles[$realPath])) {
            return [];
        }

        $loadedFiles[$realPath] = true;

        $extension = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));

        return match ($extension) {
            'json' => $this->loadJson($realPath, $loadedFiles),
            default => $this->loadTxt($realPath),
        };
    }

    /**
     * Load blocked words from a JSON file.
     *
     * Supported formats:
     *
     * - ["word_one", "word_two"]
     * - {"words": ["word_one"], "files": ["/path/to/more.txt"]}
     *
     * @param  array<string, true>  $loadedFiles
     * @return list<string>
     */
    private function loadJson(string $path, array &$loadedFiles): array
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            return [];
        }

        $decoded = json_decode($contents, true);

        if (! is_array($decoded)) {
            return [];
        }

        if ($this->isAssociativeArray($decoded)) {
            $words = [];

            if (array_key_exists('words', $decoded)) {
                $words = array_merge(
                    $words,
                    $this->loadSource($decoded['words'], $loadedFiles)
                );
            }

            if (array_key_exists('files', $decoded)) {
                $words = array_merge(
                    $words,
                    $this->loadSource($decoded['files'], $loadedFiles)
                );
            }

            return $words;
        }

        return $this->loadArray($decoded, $loadedFiles);
    }

    /**
     * Load blocked words from a TXT file.
     *
     * TXT files are treated as word lists only. Lines are not resolved as file paths.
     *
     * @return list<string>
     */
    private function loadTxt(string $path): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            return [];
        }

        $words = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $words[] = $line;
        }

        return $words;
    }

    /**
     * Normalize, filter, and de-duplicate words.
     *
     * @param  array<array-key, mixed>  $words
     * @return list<string>
     */
    private function normalizeWords(array $words): array
    {
        $normalized = [];

        foreach ($words as $word) {
            if (! is_string($word)) {
                continue;
            }

            $word = trim($word);

            if ($word === '') {
                continue;
            }

            $normalized[$word] = true;
        }

        return array_keys($normalized);
    }

    /**
     * Determine whether a string looks like a missing file path.
     */
    private function looksLikeFilePath(string $value): bool
    {
        return str_contains($value, '/')
            || str_contains($value, '\\')
            || str_ends_with(strtolower($value), '.txt')
            || str_ends_with(strtolower($value), '.json');
    }

    /**
     * Determine whether an array is associative.
     *
     * @param  array<array-key, mixed>  $array
     */
    private function isAssociativeArray(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }
}
