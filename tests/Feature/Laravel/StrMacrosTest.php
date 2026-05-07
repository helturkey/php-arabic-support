<?php

declare(strict_types=1);

namespace ArabicSupport\Tests\Feature\Laravel;

use ArabicSupport\ArabicSupportServiceProvider;
use ArabicSupport\Enums\SlugMode;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class StrMacrosTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->flushStrMacros();
        $this->registerMacrosFromProvider();
    }

    protected function tearDown(): void
    {
        $this->flushStrMacros();

        parent::tearDown();
    }

    public function test_it_registers_arabic_slug_macro(): void
    {
        $this->assertTrue(Str::hasMacro('arabicSlug'));

        $this->assertSame(
            'دليل-المستخدم-العربي',
            Str::__callStatic('arabicSlug', ['دليل المستخدم العربي'])
        );
    }

    public function test_it_registers_ascii_slug_macro(): void
    {
        $this->assertTrue(Str::hasMacro('arabicSlug'));

        $slug = Str::__callStatic('arabicSlug', ['دليل المستخدم العربي', SlugMode::Ascii]);

        $this->assertIsString($slug);
        $this->assertNotSame('', $slug);
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $slug);
    }

    public function test_it_registers_arabic_search_key_macro(): void
    {
        $this->assertTrue(Str::hasMacro('arabicSearchKey'));

        $this->assertSame(
            'احمد علي',
            Str::__callStatic('arabicSearchKey', ['أَحْمَد عَلِى'])
        );
    }

    public function test_it_registers_strip_arabic_diacritics_macro(): void
    {
        $this->assertTrue(Str::hasMacro('stripArabicDiacritics'));

        $this->assertSame(
            'محمد',
            Str::__callStatic('stripArabicDiacritics', ['مُحَمَّد'])
        );
    }

    public function test_it_registers_arabic_excerpt_macro(): void
    {
        $this->assertTrue(Str::hasMacro('arabicExcerpt'));

        $this->assertSame(
            'هذا نص...',
            Str::__callStatic('arabicExcerpt', ['هذا نص عربي طويل', 7, '...'])
        );
    }

    private function registerMacrosFromProvider(): void
    {
        $reflection = new ReflectionClass(ArabicSupportServiceProvider::class);
        $provider = $reflection->newInstanceWithoutConstructor();

        $method = $reflection->getMethod('registerMacros');
        $method->setAccessible(true);
        $method->invoke($provider);
    }

    private function flushStrMacros(): void
    {
        $reflection = new ReflectionClass(Str::class);

        if (! $reflection->hasProperty('macros')) {
            return;
        }

        $property = $reflection->getProperty('macros');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }
}
