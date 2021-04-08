<?php

namespace Kirby\Customers\Tests\Feature\API\V1;

use Kirby\Customers\Models\Customer;
use Kirby\Users\Models\User;
use Tests\TestCase;

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
     * @var \Kirby\Users\Models\User
     */
    private $user;

    /**
     * @var \Illuminate\Support\Collection<\Kirby\Customers\Models\Customer>
     */
    private $customers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin($this->user = factory(User::class)->create());
        $this->customers = factory(Customer::class, 5)->create();
    }

    /**
     * @test
     */
    public function shouldReturnResourcePaginatedList()
    {
        $this->json($this->method, $this->endpoint)
            ->assertOk()
            ->assertJsonCount($this->customers->count(), 'data');
    }
}
