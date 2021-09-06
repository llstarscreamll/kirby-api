<?php

namespace Kirby\Machines\Tests\Feature\API\V1;

use Kirby\Machines\Models\Machine;
use Kirby\Users\Models\User;
use Tests\TestCase;

class SearchMachinesTest extends TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/machines';

    /**
     * @var string
     */
    private $method = 'GET';

    /**
     * @var \Kirby\Users\Models\User
     */
    private $user;

    /**
     * @var \Illuminate\Support\Collection<\Kirby\Machines\Models\Machine>
     */
    private $machines;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin($this->user = factory(User::class)->create());
        $this->machines = factory(Machine::class, 5)->create();
    }

    /**
     * @test
     */
    public function shouldReturnResourcePaginatedList()
    {
        $this->json($this->method, $this->endpoint)
            ->assertOk()
            ->assertJsonCount($this->machines->count(), 'data');
    }
}
