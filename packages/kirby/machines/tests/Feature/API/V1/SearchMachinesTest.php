<?php

namespace Kirby\Machines\Tests\Feature\API\V1;

use Illuminate\Support\Collection;
use Kirby\Machines\Models\Machine;
use Kirby\Users\Models\User;
use Tests\TestCase;

/**
 * @internal
 */
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

    private Collection $machines;

    protected function setUp(): void
    {
        parent::setUp();

        $this->machines = factory(Machine::class, 5)->create();
    }

    /**
     * @test
     */
    public function shouldReturnPaginatedMachinesList()
    {
        $this->actingAsAdmin(factory(User::class)->create())
            ->json($this->method, $this->endpoint)
            ->assertOk()
            ->assertJsonCount($this->machines->count(), 'data');
    }

    /**
     * @test
     */
    public function shouldSearchByManyCostCenterIDs()
    {
        $machines = factory(Machine::class, 2)->create();
        $costCenterIDs = $machines->map->subCostCenter->pluck('cost_center_id');

        $this->actingAsAdmin(factory(User::class)->create())
            ->json($this->method, $this->endpoint, ['filter' => ['cost_center_ids' => $costCenterIDs->all()]])
            ->assertOk()
            ->assertJsonCount($costCenterIDs->count(), 'data');
    }
}
