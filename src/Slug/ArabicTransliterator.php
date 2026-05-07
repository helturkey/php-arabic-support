<?php

declare(strict_types=1);

namespace ArabicSupport\Slug;

use ArabicSupport\Enums\ArabicPolicy;
use ArabicSupport\Normalization\ArabicNormalizer;
use ArabicSupport\Normalization\DiacriticsStripper;
use ArabicSupport\Normalization\TatweelStripper;
use ArabicSupport\Numbers\ArabicDigits;
use Transliterator;

/**
 * Converts Arabic text to a Latin/ASCII approximation.
 */
final class ArabicTransliterator
{
    /** @var array<string,string> */
    private const MAP = [
        'ء' => '',
        'أ' => 'a', 'إ' => 'i', 'آ' => 'a', 'ٱ' => 'a', 'ا' => 'a',
        'ب' => 'b', 'ت' => 't', 'ث' => 'th', 'ج' => 'j',
        'ح' => 'h', 'خ' => 'kh', 'د' => 'd', 'ذ' => 'dh', 'ر' => 'r',
        'ز' => 'z', 'س' => 's', 'ش' => 'sh', 'ص' => 's', 'ض' => 'd',
        'ط' => 't', 'ظ' => 'z', 'ع' => 'a', 'غ' => 'gh', 'ف' => 'f',
        'ق' => 'q', 'ك' => 'k', 'ک' => 'k', 'ل' => 'l', 'م' => 'm',
        'ن' => 'n', 'ه' => 'h', 'ة' => 'a', 'و' => 'w', 'ؤ' => 'w',
        'ي' => 'y', 'ی' => 'y', 'ى' => 'a', 'ئ' => 'y',
    ];

    public function __construct(
        private readonly ArabicNormalizer $normalizer = new ArabicNormalizer,
        private readonly TatweelStripper $tatweel = new TatweelStripper,
        private readonly DiacriticsStripper $diacritics = new DiacriticsStripper,
        private readonly ArabicDigits $digits = new ArabicDigits,
    ) {}

    /**
     * Transliterate text to ASCII.
     *
     * When $normalize is true, slug-oriented normalization is applied before
     * transliteration. Set it to false when you want a closer transliteration of
     * the original spelling while still removing non-ASCII combining marks.
     */
    public function toAscii(string $text, bool $normalize = true): string
    {
        if ($normalize) {
            $text = $this->normalizer->normalize($text, ArabicPolicy::Slug);
        } else {
            $text = $this->tatweel->strip($text);
            $text = $this->diacritics->strip($text, true);
            $text = $this->digits->toLatin($text);
        }

        if (class_exists(Transliterator::class)) {
            $transliterator = Transliterator::create('Any-Latin; Latin-ASCII; NFD; [:Nonspacing Mark:] Remove; NFC');
            if ($transliterator instanceof Transliterator) {
                $result = $transliterator->transliterate($text);
                if (is_string($result) && $result !== '') {
                    return preg_replace('/[^\x20-\x7E]/', '', $this->digits->toLatin($result)) ?: '';
                }
            }
        }

        $result = strtr($text, self::MAP);

        return preg_replace('/[^\x20-\x7E]/', '', $this->digits->toLatin($result)) ?: '';
    }
}
