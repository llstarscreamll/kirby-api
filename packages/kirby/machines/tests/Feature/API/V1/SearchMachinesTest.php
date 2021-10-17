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
     * @test
     */
    public function shouldReturnPaginatedMachinesList()
    {
        $machines = factory(Machine::class, 5)->create();

        $this->actingAsAdmin(factory(User::class)->create())
            ->json($this->method, $this->endpoint)
            ->assertOk()
            ->assertJsonCount($machines->count(), 'data');
    }
}
