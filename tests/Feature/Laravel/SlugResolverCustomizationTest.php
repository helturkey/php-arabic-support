<?php

declare(strict_types=1);

namespace ArabicSupport\Tests\Feature\Laravel;

use ArabicSupport\Tests\Fixtures\Models\WriterArticle;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use PHPUnit\Framework\TestCase;

final class SlugResolverCustomizationTest extends TestCase
{
    private Capsule $capsule;

    protected function setUp(): void
    {
        parent::setUp();

        Model::clearBootedModels();

        $this->capsule = new Capsule;
        $this->capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $this->capsule->setEventDispatcher(new Dispatcher(new Container));
        $this->capsule->setAsGlobal();
        $this->capsule->bootEloquent();

        Model::clearBootedModels();

        $this->createSchema();
    }

    protected function tearDown(): void
    {
        Capsule::schema()->dropIfExists('articles');
        Model::clearBootedModels();

        parent::tearDown();
    }

    public function test_model_can_customize_unique_slug_strategy_with_writer_name(): void
    {
        $article = WriterArticle::create([
            'title' => 'دليل المستخدم العربي',
            'writer' => 'أحمد علي',
        ]);

        $this->assertSame('دليل-المستخدم-العربي-أحمد-علي', $article->slug);
        $this->assertSame('احمد علي', $article->writer_search);
    }

    public function test_custom_unique_slug_strategy_can_fallback_to_numbered_strategy(): void
    {
        WriterArticle::create([
            'title' => 'دليل المستخدم العربي',
        ]);

        WriterArticle::create([
            'title' => 'دليل المستخدم العربي',
            'writer' => 'أحمد علي',
        ]);

        $third = WriterArticle::create([
            'title' => 'دليل المستخدم العربي',
            'writer' => 'أحمد علي',
        ]);

        $this->assertSame('دليل-المستخدم-العربي-2', $third->slug);
    }

    private function createSchema(): void
    {
        Capsule::schema()->create('articles', static function ($table): void {
            $table->increments('id');
            $table->string('title');
            $table->string('title_search')->nullable();
            $table->string('writer')->nullable();
            $table->string('writer_search')->nullable();
            $table->string('slug')->nullable()->unique();
            $table->timestamps();
        });
    }
}
