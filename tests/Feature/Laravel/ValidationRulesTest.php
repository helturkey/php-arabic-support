<?php

declare(strict_types=1);

namespace ArabicSupport\Tests\Feature\Laravel;

use ArabicSupport\Laravel\Rules\ArabicName;
use ArabicSupport\Laravel\Rules\ArabicSlug;
use ArabicSupport\Laravel\Rules\ArabicText;
use ArabicSupport\Laravel\Rules\ContainsArabic;
use ArabicSupport\Laravel\Rules\NoBadWords;
use ArabicSupport\Laravel\Rules\NoBidiControls;
use ArabicSupport\Laravel\Rules\NoInvisibleCharacters;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Translation\Translator;
use PHPUnit\Framework\TestCase;

final class ValidationRulesTest extends TestCase
{
    public function test_arabic_text_accepts_text_above_min_ratio(): void
    {
        $this->assertRulePasses(new ArabicText(minRatio: 0.6), 'مرحبا بكم');
    }

    public function test_arabic_text_rejects_text_below_min_ratio(): void
    {
        $this->assertRuleFails(new ArabicText(minRatio: 0.8), 'hello مرحبا');
    }

    public function test_contains_arabic_requires_one_arabic_character(): void
    {
        $this->assertRulePasses(new ContainsArabic, 'Order رقم 123');
        $this->assertRuleFails(new ContainsArabic, 'Order 123');
    }

    public function test_arabic_slug_accepts_unicode_slug(): void
    {
        $this->assertRulePasses(new ArabicSlug, 'دليل-المستخدم-2026');
    }

    public function test_arabic_slug_rejects_invalid_slug(): void
    {
        $this->assertRuleFails(new ArabicSlug, 'دليل المستخدم!');
    }

    public function test_arabic_name_accepts_general_arabic_name(): void
    {
        $this->assertRulePasses(new ArabicName, 'أحمد علي');
    }

    public function test_arabic_name_rejects_unsupported_characters(): void
    {
        $this->assertRuleFails(new ArabicName, 'أحمد <script>');
    }

    public function test_no_bidi_controls_rejects_bidi_control_characters(): void
    {
        $this->assertRuleFails(new NoBidiControls, "abc\u{202E}def");
    }

    public function test_no_invisible_characters_rejects_zero_width_characters(): void
    {
        $this->assertRuleFails(new NoInvisibleCharacters, "abc\u{200B}def");
    }

    public function test_no_bad_words_uses_explicit_list(): void
    {
        $this->assertRuleFails(new NoBadWords(['محظور']), 'هذا نص محظور');
        $this->assertRulePasses(new NoBadWords(['محظور']), 'هذا نص عادي');
    }

    private function assertRulePasses(ValidationRule $rule, mixed $value): void
    {
        $failed = false;

        $rule->validate('field', $value, $this->failClosure($failed));

        $this->assertFalse($failed);
    }

    private function assertRuleFails(ValidationRule $rule, mixed $value): void
    {
        $failed = false;

        $rule->validate('field', $value, $this->failClosure($failed));

        $this->assertTrue($failed);
    }

    /**
     * Create a Laravel-compatible validation failure closure.
     *
     * @param  bool  $failed  Passed by reference and set to true when validation fails.
     * @return Closure(string, string|null=): PotentiallyTranslatedString
     */
    private function failClosure(bool &$failed): Closure
    {
        return static function (string $message, ?string $translate = null) use (&$failed): PotentiallyTranslatedString {
            $failed = true;

            return new PotentiallyTranslatedString(
                $message,
                new Translator(new ArrayLoader, 'en')
            );
        };
    }
}
