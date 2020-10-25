<?php

namespace Kirby\Products\Tests\Feature\API\V1;

use Illuminate\Database\Eloquent\Factory as EloquentFactory;
use Illuminate\Support\Facades\DB;
use Kirby\Products\Models\Category;
use Kirby\Products\Models\Product;
use ProductsPackageSeed;
use Tests\TestCase;

/**
 * Class SearchCategoriesTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SearchCategoriesTest extends TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/categories';

    /**
     * @var string
     */
    private $method = 'GET';

    public function setUp(): void
    {
        parent::setUp();

        $this->app->make(EloquentFactory::class)->load(__DIR__.'/../database/factories');
        $this->seed(ProductsPackageSeed::class);
    }

    /**
     * @test
     */
    public function shouldReturnPaginatedListWithAllCategories()
    {
        $this->json($this->method, $this->endpoint)
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('data.0.type', 'Category')
            ->assertJsonPath('data.0.id', '4') // default sorting by id desc
            ->assertJsonPath('data.1.id', '3')
            ->assertJsonPath('data.2.id', '2')
            ->assertJsonPath('data.3.id', '1')
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonStructure(['links' => ['self', 'first', 'last']])
            ->assertJsonStructure(['meta' => ['pagination' => ['total', 'count', 'per_page', 'current_page', 'total_pages']]]);
    }

    /**
     * @test
     */
    public function shouldSearchByActiveCategories()
    {
        $this->json($this->method, $this->endpoint, ['filter' => ['active' => true]])
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    /**
     * @test
     */
    public function shouldSortResultsByPosition()
    {
        $this->json($this->method, $this->endpoint, ['sort' => 'position'])
            ->assertOk()
            ->assertJsonPath('data.0.attributes.position', 1)
            ->assertJsonPath('data.1.attributes.position', 2)
            ->assertJsonPath('data.2.attributes.position', 3)
            ->assertJsonPath('data.3.attributes.position', 4);
    }

    /**
     * @test
     */
    public function shouldReturnCategoryWithTheFirstTenRelatedProducts()
    {
        // delete all relations
        DB::table('category_product')->delete();
        // set related products to first two categories
        $categories = Category::orderBy('id', 'desc')->get();
        $categories[0]->products()->sync(factory(Product::class, 15)->create());
        $categories[1]->products()->sync(factory(Product::class, 15)->create());

        $this->json($this->method, $this->endpoint, ['include' => 'firstTenProducts'])
            ->assertOk()
            ->assertJsonCount(Category::count(), 'data')
            ->assertJsonCount(10, 'data.0.relationships.firstTenProducts.data')
            ->assertJsonCount(10, 'data.1.relationships.firstTenProducts.data');
    }
}
