<?php

namespace Kirby\Products\Tests\Feature\API\V1;

use Illuminate\Database\Eloquent\Factory as EloquentFactory;
use Kirby\Products\Models\Category;
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
    public function shouldSearchActiveCategories()
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
    public function shouldReturnCategoryWithTheFirstRelatedProducts()
    {
        Category::first()->products()->delete();
        $this->json($this->method, $this->endpoint, ['include' => 'firstTenProducts'])
            ->assertOk()
            ->assertJsonPath('data.0.id', 4)
            ->assertJsonPath('data.0.relationships.firstTenProducts.data.0.id', 7)
            ->assertJsonPath('data.0.relationships.firstTenProducts.data.0.type', 'Product')
            ->assertJsonPath('included.0.type', 'Product')
            ->assertJsonStructure(['data', 'included', 'links', 'meta']);
    }
}
