<?php

namespace Kirby\Products\Tests\Feature\API\V1;

use Illuminate\Support\Collection;
use Kirby\Products\Models\Product;
use Kirby\Users\Models\User;
use ProductsPackageSeed;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * @internal
 */
class UpdateProductTest extends TestCase
{
    private string $endpoint = 'api/v1/products/:id';

    private string $method = 'PUT';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ProductsPackageSeed::class);
        $this->actingAsAdmin($this->user = factory(User::class)->create());
    }

    /**
     * Should update product successfully when input is ok.
     *
     * @test
     */
    public function shouldUpdateProductSuccessfully()
    {
        $product = factory(Product::class)->create();
        $input = factory(Product::class)->make()->toArray();

        $this->json($this->method, str_replace(':id', $product->id, $this->endpoint), $input)
            ->assertOk()
            ->assertJsonPath('data', 'ok');

        $this->assertDatabaseHas('products', ['id' => $product->id] + $input);
    }

    /**
     * Should return unprocessable entity when input is empty.
     *
     * @test
     */
    public function shouldReturnUnprocessableEntityWhenInputIsEmpty()
    {
        $product = factory(Product::class)->create();

        $this->json($this->method, str_replace(':id', $product->id, $this->endpoint), [])
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
        $product = factory(Product::class)->create();

        $this->json($this->method, str_replace(':id', $product->id, $this->endpoint), [])->assertForbidden();
    }
}
