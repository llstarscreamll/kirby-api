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
    private $activeProducts;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin($this->user = factory(User::class)->create());
        factory(Product::class, 3)->create(['active' => false]);
        $this->activeProducts = factory(Product::class, 5)->create();
    }

    /**
     * @test
     */
    public function shouldReturnResourcePaginatedList()
    {
        $this->json($this->method, $this->endpoint)
            ->assertOk()
            ->assertJsonCount($this->activeProducts->count(), 'data')
            ->assertJsonPath('data.0.id', $this->activeProducts->last()->id)
            ->assertJsonPath('data.4.id', $this->activeProducts->first()->id);
    }
}
