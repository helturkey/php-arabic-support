<?php

declare(strict_types=1);

namespace ArabicSupport\Tests\Feature\Laravel;

use ArabicSupport\Tests\Fixtures\Models\Article;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use PHPUnit\Framework\TestCase;

final class EloquentTraitsTest extends TestCase
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

        // Eloquent stores booted models statically. Clearing here ensures trait boot
        // methods are re-registered for every fresh in-memory database.
        Model::clearBootedModels();

        $this->createSchema();
    }

    protected function tearDown(): void
    {
        Capsule::schema()->dropIfExists('articles');
        Model::clearBootedModels();

        parent::tearDown();
    }

    public function test_has_arabic_slug_generates_slug_before_saving(): void
    {
        $article = Article::create([
            'title' => 'دليل المستخدم العربي',
            'body' => 'محتوى عربي عام',
        ]);

        $this->assertSame('دليل-المستخدم-العربي', $article->slug);
    }

    public function test_has_arabic_slug_keeps_existing_slug(): void
    {
        $article = Article::create([
            'title' => 'دليل المستخدم العربي',
            'slug' => 'custom-slug',
            'body' => 'محتوى عربي عام',
        ]);

        $this->assertSame('custom-slug', $article->slug);
    }

    public function test_has_arabic_slug_generates_unique_slug(): void
    {
        Article::create([
            'title' => 'دليل المستخدم العربي',
            'body' => 'محتوى عربي عام',
        ]);

        $second = Article::create([
            'title' => 'دليل المستخدم العربي',
            'body' => 'محتوى عربي آخر',
        ]);

        $this->assertSame('دليل-المستخدم-العربي-2', $second->slug);
    }

    public function test_has_arabic_search_keys_populates_search_columns(): void
    {
        $article = Article::create([
            'title' => 'أَحْمَد عَلِى',
            'body' => 'مَدْرَسَة عربية',
        ]);

        $this->assertSame('احمد علي', $article->title_search);
        $this->assertSame('مدرسه عربيه', $article->body_search);
    }

    public function test_where_arabic_scope_normalizes_value(): void
    {
        Article::create([
            'title' => 'أَحْمَد عَلِى',
            'body' => 'محتوى عام',
        ]);

        $query = (new Article)->scopeWhereArabic(
            Article::query(),
            'title_search',
            'احمد علي'
        );

        $this->assertInstanceOf(Article::class, $query->first());
    }

    public function test_where_arabic_contains_scope_normalizes_value(): void
    {
        Article::create([
            'title' => 'تقرير المبيعات',
            'body' => 'هذا محتوى عن إدارة المبيعات السنوية',
        ]);

        $query = (new Article)->scopeWhereArabicContains(
            Article::query(),
            'body_search',
            'المبيعات'
        );

        $this->assertInstanceOf(Article::class, $query->first());
    }

    private function createSchema(): void
    {
        Capsule::schema()->create('articles', static function ($table): void {
            $table->increments('id');
            $table->string('title');
            $table->string('title_search')->nullable();
            $table->text('body')->nullable();
            $table->text('body_search')->nullable();
            $table->string('slug')->nullable()->unique();
            $table->timestamps();
        });
    }
}
