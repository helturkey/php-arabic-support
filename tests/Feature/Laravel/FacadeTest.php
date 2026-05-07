<?php

declare(strict_types=1);

namespace ArabicSupport\Tests\Feature\Laravel;

use ArabicSupport\ArabicManager;
use ArabicSupport\Laravel\Facades\Arabic;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;

final class FacadeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $app = new Container;
        $app->singleton('php-arabic', static fn (): ArabicManager => new ArabicManager);

        /** @phpstan-ignore-next-line Container is enough for this lightweight facade test. */
        Facade::setFacadeApplication($app);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);

        parent::tearDown();
    }

    public function test_facade_resolves_manager(): void
    {
        $this->assertSame(
            'دليل-المستخدم',
            Arabic::unicodeSlug('دليل المستخدم')
        );
    }
}
