<?php

namespace Kirby\Products\Tests\Feature\API\V1;

use Kirby\Products\Models\Product;
use Kirby\Users\Models\User;
use ProductsPackageSeed;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * @internal
 */
class CreateProductsTest extends TestCase
{
    private string $endpoint = 'api/v1/products';

    private string $method = 'POST';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ProductsPackageSeed::class);
        $this->actingAsAdmin($this->user = factory(User::class)->create());
    }

    /**
     * Should create product successfully when input is ok.
     *
     * @test
     */
    public function shouldCreateProductsSuccessfully()
    {
        $input = factory(Product::class)->make()->toArray();

        $this->json($this->method, $this->endpoint, $input)
            ->assertCreated()
            ->assertJsonStructure(['data' => [
                'id', 'name', 'short_name', 'internal_code', 'customer_code', 'wire_gauge_in_bwg', 'wire_gauge_in_mm',
            ]]);

        $this->assertDatabaseHas('products', $input);
    }

    /**
     * Should return unprocessable entity when input is empty.
     *
     * @test
     */
    public function shouldReturnUnprocessableEntityWhenInputIsEmpty()
    {
        $this->json($this->method, $this->endpoint, [])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Should return forbidden when user doesn't have enough permissions.
     *
     * @test
     */
    public function shouldReturnForbiddenWhenUserDoesNotHavePermissions()
    {
        $this->user->permissions()->delete();

        $this->json($this->method, $this->endpoint, [])->assertForbidden();
    }
}
