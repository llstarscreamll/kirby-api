<?php

namespace Kirby\Products\Tests\Feature\API\V1;

use Illuminate\Database\Eloquent\Factory as EloquentFactory;
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
            ->assertJsonPath('data.0.id', '1') // default sorting by id
            ->assertJsonPath('data.1.id', '2')
            ->assertJsonPath('data.2.id', '3')
            ->assertJsonPath('data.3.id', '4');
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
}
