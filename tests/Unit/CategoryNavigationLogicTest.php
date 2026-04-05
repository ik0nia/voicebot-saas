<?php

namespace Tests\Unit;

use App\Services\CategoryNavigationService;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\CacheManager;
use Illuminate\Cache\Repository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class CategoryNavigationLogicTest extends TestCase
{
    private CategoryNavigationService $service;
    private ReflectionMethod $removeDiacritics;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up a minimal in-memory Cache facade so public methods
        // can call Cache::remember without a real database.
        $app = new Container();
        $cacheRepo = new Repository(new ArrayStore());

        $manager = new class ($cacheRepo) extends CacheManager {
            private Repository $repo;

            public function __construct(Repository $repo)
            {
                $this->repo = $repo;
            }

            public function store($name = null)
            {
                return $this->repo;
            }

            public function __call($method, $parameters)
            {
                return $this->repo->$method(...$parameters);
            }
        };

        $app->instance('cache', $manager);
        Facade::setFacadeApplication($app);

        // Pre-seed cache keys for bot_id=0 so the Cache::remember closure
        // (which would hit Eloquent/DB) is never executed.
        Cache::put('bot_brands_0', [], 3600);
        Cache::put('bot_categories_0', [], 3600);

        $this->service = new CategoryNavigationService();

        $this->removeDiacritics = new ReflectionMethod(CategoryNavigationService::class, 'removeDiacritics');
        $this->removeDiacritics->setAccessible(true);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);

        parent::tearDown();
    }

    // ------------------------------------------------------------------
    //  Tests 1-3: removeDiacritics (reflection on private method)
    // ------------------------------------------------------------------

    /**
     * Test 1: removeDiacritics converts lowercase Romanian diacritics.
     */
    public function test_remove_diacritics_converts_lowercase_romanian_characters(): void
    {
        $result = $this->removeDiacritics->invoke($this->service, 'ăâîșț');

        $this->assertSame('aaist', $result);
    }

    /**
     * Test 2: removeDiacritics converts uppercase Romanian diacritics.
     */
    public function test_remove_diacritics_converts_uppercase_romanian_characters(): void
    {
        $result = $this->removeDiacritics->invoke($this->service, 'ĂÂÎȘȚ');

        $this->assertSame('AAIST', $result);
    }

    /**
     * Test 3: removeDiacritics handles mixed text with diacritics and numbers.
     */
    public function test_remove_diacritics_handles_mixed_text_with_numbers(): void
    {
        $input = 'Preț: 150 lei, Număr: 42, Șarpe în grădină';
        $expected = 'Pret: 150 lei, Numar: 42, Sarpe in gradina';

        $result = $this->removeDiacritics->invoke($this->service, $input);

        $this->assertSame($expected, $result);
    }

    // ------------------------------------------------------------------
    //  Tests 4-5: detectBrandQuery
    // ------------------------------------------------------------------

    /**
     * Test 4: detectBrandQuery returns null for empty string.
     */
    public function test_detect_brand_query_returns_null_for_empty_string(): void
    {
        $result = $this->service->detectBrandQuery(0, '');

        $this->assertNull($result);
    }

    /**
     * Test 5: detectBrandQuery returns null for bot_id with no products.
     */
    public function test_detect_brand_query_returns_null_for_bot_with_no_products(): void
    {
        $result = $this->service->detectBrandQuery(0, 'looking for Weber products');

        $this->assertNull($result);
    }

    // ------------------------------------------------------------------
    //  Tests 6-7: detectCategoryQuery
    // ------------------------------------------------------------------

    /**
     * Test 6: detectCategoryQuery returns null for empty string.
     */
    public function test_detect_category_query_returns_null_for_empty_string(): void
    {
        $result = $this->service->detectCategoryQuery(0, '');

        $this->assertNull($result);
    }

    /**
     * Test 7: detectCategoryQuery returns null for bot_id with no products.
     */
    public function test_detect_category_query_returns_null_for_bot_with_no_products(): void
    {
        $result = $this->service->detectCategoryQuery(0, 'vopsea lavabila');

        $this->assertNull($result);
    }

    // ------------------------------------------------------------------
    //  Tests 8-9: buildNavigationContext
    // ------------------------------------------------------------------

    /**
     * Test 8: buildNavigationContext returns null when no brand/category matches.
     */
    public function test_build_navigation_context_returns_null_when_no_matches(): void
    {
        $result = $this->service->buildNavigationContext(0, 'some random text with no matches');

        $this->assertNull($result);
    }

    /**
     * Test 9: buildNavigationContext returns null for empty transcript.
     */
    public function test_build_navigation_context_returns_null_for_empty_transcript(): void
    {
        $result = $this->service->buildNavigationContext(0, '');

        $this->assertNull($result);
    }

    // ------------------------------------------------------------------
    //  Test 10: Instantiation
    // ------------------------------------------------------------------

    /**
     * Test 10: Service can be instantiated without errors.
     */
    public function test_service_can_be_instantiated(): void
    {
        $service = new CategoryNavigationService();

        $this->assertInstanceOf(CategoryNavigationService::class, $service);
    }
}
