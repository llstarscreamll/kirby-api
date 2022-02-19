<?php

namespace Kirby\Products\Tests\Feature\API\V1;

use Kirby\Products\Models\Product;
use Kirby\Users\Models\User;
use Tests\TestCase;

/**
 * @internal
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

    /**
     * @var \Kirby\Users\Models\User
     */
    private $user;

    /**
     * @var \Illuminate\Support\Collection<\Kirby\Products\Models\Product>
     */
    private $products;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin($this->user = factory(User::class)->create());
        $this->products = factory(Product::class, 5)->create();
    }

    /**
     * @test
     */
    public function shouldReturnResourcePaginatedList()
    {
        $this->json($this->method, $this->endpoint)
            ->assertOk()
            ->assertJsonCount($this->products->count(), 'data');
    }
}
