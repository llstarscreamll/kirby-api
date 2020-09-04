<?php

namespace Kirby\Products\Tests\Feature\API\V1;

use Illuminate\Database\Eloquent\Factory as EloquentFactory;
use ProductsPackageSeed;
use Tests\TestCase;

/**
 * Class SearchProductsTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SearchProductsTest extends TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/products';

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
    public function shouldReturnPaginatedListWithAllProducts()
    {
        $this->json($this->method, $this->endpoint)
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('data.0.type', 'Products')
            ->assertJsonPath('data.0.id', '1') // default sorting by id
            ->assertJsonPath('data.1.id', '2')
            ->assertJsonPath('data.2.id', '3')
            ->assertJsonPath('data.3.id', '4');
    }
}
