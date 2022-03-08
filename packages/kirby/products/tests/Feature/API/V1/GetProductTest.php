<?php

namespace Kirby\Products\Tests\Feature\API\V1;

use Kirby\Products\Models\Product;
use Kirby\Users\Models\User;
use ProductsPackageSeed;
use Tests\TestCase;

/**
 * @internal
 */
class GetProductTest extends TestCase
{
    private string $endpoint = 'api/v1/products/:id';

    private string $method = 'GET';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ProductsPackageSeed::class);
        $this->actingAsAdmin($this->user = factory(User::class)->create());
    }

    /**
     * Should get product successfully when given ID exist.
     *
     * @test
     */
    public function shouldGetProductWhenGivenIdExist()
    {
        $product = factory(Product::class)->create();

        $this->json($this->method, str_replace(':id', $product->id, $this->endpoint))
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.short_name', $product->short_name)
            ->assertJsonPath('data.internal_code', $product->internal_code)
            ->assertJsonPath('data.customer_code', $product->customer_code)
            ->assertJsonPath('data.wire_gauge_in_bwg', $product->wire_gauge_in_bwg)
            ->assertJsonPath('data.wire_gauge_in_mm', $product->wire_gauge_in_mm);
    }

    /**
     * Should return not found when product ID does not exist.
     *
     * @test
     */
    public function shouldReturnNotFoundWhenIdDoesNotExist()
    {
        $this->json($this->method, str_replace(':id', 123456789, $this->endpoint), [])
            ->assertNotFound();
    }

    /**
     * Should return forbidden when user doesn't have enough permissions.
     *
     * @test
     */
    public function shouldReturnForbiddenWhenUserDoesNotHavePermissions()
    {
        $this->user->permissions()->delete();
        $product = factory(Product::class)->create();

        $this->json($this->method, str_replace(':id', $product->id, $this->endpoint), [])->assertForbidden();
    }
}
