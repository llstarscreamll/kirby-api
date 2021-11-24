<?php

namespace Kirby\Production\Tests\Feature\API\V1;

use Kirby\Employees\Models\Employee;
use Kirby\Production\Models\ProductionLog;
use Kirby\Users\Models\User;
use ProductionPackageSeed;
use Tests\TestCase;

class SearchProductionLogsTest extends TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/production-logs';

    /**
     * @var string
     */
    private $method = 'GET';

    /**
     * @var \Kirby\Users\Models\User
     */
    private $user;

    /**
     * @var \Kirby\Employees\Models\Employee
     */
    private $employee;

    /**
     * @var \Illuminate\Support\Collection<\Kirby\Production\Models\ProductionLog>
     */
    private $productionLogs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ProductionPackageSeed::class);
        $this->actingAsAdmin($this->user = factory(User::class)->create());
        $this->employee = factory(Employee::class)->create(['id' => $this->user->id]);
        $this->productionLogs = factory(ProductionLog::class, 5)->create(['employee_id' => $this->employee]);
    }

    /**
     * @test
     */
    public function shouldReturnAllItemsWhenNQueryParamsAreGiven()
    {
        $this->json($this->method, $this->endpoint)
            ->assertOk()
            ->assertJsonCount($this->productionLogs->count(), 'data');
    }

    /**
     * @test
     */
    public function shouldSearchByNetWeight()
    {
        ProductionLog::first()->update(['gross_weight' => 22.22, 'tare_weight' => 0]);

        $this->json($this->method, $this->endpoint, ['filter' => ['net_weight' => 22.22]])
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    /**
     * @test
     */
    public function shouldReturnForbidenWhenUserDoesNotHavePermissions()
    {
        $this->user->permissions()->delete();

        $this->json($this->method, $this->endpoint, [])->assertForbidden();
    }
}
