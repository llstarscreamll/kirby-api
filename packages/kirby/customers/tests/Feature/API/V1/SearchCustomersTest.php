<?php

namespace Kirby\Customers\Tests\Feature\API\V1;

use Kirby\Customers\Models\Customer;
use Kirby\Users\Models\User;
use Tests\TestCase;

/**
 * @internal
 */
class SearchCustomersTest extends TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/customers';

    /**
     * @var string
     */
    private $method = 'GET';

    /**
     * @test
     */
    public function shouldReturnResourcePaginatedList()
    {
        $customers = factory(Customer::class, 5)->create();

        $this->actingAsAdmin(factory(User::class)->create())
            ->json($this->method, $this->endpoint)
            ->assertOk()
            ->assertJsonCount($customers->count(), 'data');
    }
}
