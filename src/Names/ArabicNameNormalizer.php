<?php

declare(strict_types=1);

namespace ArabicSupport\Names;

use ArabicSupport\Cleaning\TextCleaner;
use ArabicSupport\Compat\StringSupport;
use ArabicSupport\Normalization\DiacriticsStripper;
use ArabicSupport\Normalization\TatweelStripper;

/**
 * Normalizes common Arabic name spelling for display-oriented use cases.
 *
 * The default behavior is conservative: it does not convert alef maqsura (ى)
 * to ya (ي), because that can change display names such as "عيسى".
 */
final class ArabicNameNormalizer
{
    /** @var array<string,string> */
    private array $corrections;

    /**
     * @param  array<string,string>  $corrections  Optional spelling corrections keyed by normalized input.
     */
    public function __construct(array $corrections = [])
    {
        $this->corrections = $corrections ?: [
            'احمد' => 'أحمد',
            'امين' => 'أمين',
            'اسامة' => 'أسامة',
            'جمعه' => 'جمعة',
            'امنة' => 'آمنة',
            'ابان' => 'أبان',
            'ابراهيم' => 'إبراهيم',
            'اسحاق' => 'إسحاق',
            'الياس' => 'إلياس',
            'اسلام' => 'إسلام',
            'اسماعيل' => 'إسماعيل',
            'اليسع' => 'إليسع',
        ];
    }

    /**
     * Normalize a general Arabic display name.
     *
     * @param  int  $maxWords  Maximum number of words to keep. Use 0 to keep all words.
     * @param  bool  $applyCorrections  Apply configured display-name corrections such as احمد => أحمد.
     * @param  bool  $normalizeAlefMaqsura  Convert ى to ي. Disabled by default for display safety.
     */
    public function normalize(
        string $name,
        int $maxWords = 8,
        bool $applyCorrections = true,
        bool $normalizeAlefMaqsura = false,
    ): string {
        $cleaner = new TextCleaner;
        $name = $cleaner->keepTextCharacters($name, ' -', false, ' ');
        $name = (new TatweelStripper)->strip($name);
        $name = (new DiacriticsStripper)->strip($name, true);
        $name = $cleaner->normalizeInlineWhitespace($name);

        $name = str_replace(
            ['عبدال', 'ابوال', 'أبوال', ' ابن '],
            ['عبد ال', 'أبو ال', 'أبو ال', ' بن '],
            $name
        );

        if ($normalizeAlefMaqsura) {
            $name = str_replace('ى', 'ي', $name);
        }

        if (StringSupport::startsWith($name, 'بن ')) {
            $name = 'ابن '.StringSupport::substr($name, 3);
        }

        $words = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY);
        if (! is_array($words)) {
            return $name;
        }

        if ($maxWords > 0) {
            $words = array_slice($words, 0, $maxWords);
        }

        if ($applyCorrections) {
            foreach ($words as $index => $word) {
                $key = StringSupport::lower($word);
                if (isset($this->corrections[$key])) {
                    $words[$index] = $this->corrections[$key];
                }
            }
        }

        return implode(' ', $words);
    }
}
