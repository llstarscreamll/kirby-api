<?php

use Illuminate\Support\Facades\DB;
use Kirby\Company\Models\SubCostCenter;
use Kirby\Employees\Models\Employee;
use Kirby\Machines\Models\Machine;
use Kirby\Production\Enums\Purpose;
use Kirby\Production\Enums\Tag;
use Kirby\Production\Models\ProductionLog;
use Kirby\Products\Models\Product;
use Kirby\Users\Models\User;
use Tests\TestCase;

/**
 * @internal
 */
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
        $this->productionLogs = factory(ProductionLog::class, 5)->create(['employee_id' => $this->employee, 'tag_updated_at' => now()->subMonth()]);
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
    public function shouldSearchByTagDateRange()
    {
        DB::table('production_logs')
            ->where('id', $this->productionLogs->first()->id)
            ->update(['tag_updated_at' => now()]);

        $this->json($this->method, $this->endpoint, ['filter' => [
            'tag_updated_at' => [
                'start' => now()->startOfDay()->toISOString(),
                'end' => now()->endOfDay()->toISOString(),
            ],
        ]])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', ProductionLog::first()->id);
    }

    /**
     * @test
     */
    public function shouldSearchByManyTags()
    {
        DB::table('production_logs')->update(['tag' => Tag::Rejected]);
        $this->productionLogs->get(1)->update(['tag' => Tag::InLine]);
        $this->productionLogs->get(2)->update(['tag' => Tag::Error]);

        $this->json($this->method, $this->endpoint, ['filter' => ['tags' => [Tag::InLine, Tag::Error]]])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $this->productionLogs->get(2)->id)
            ->assertJsonPath('data.1.id', $this->productionLogs->get(1)->id);
    }

    /**
     * @test
     */
    public function shouldSearchByCertainPurpose()
    {
        $this->productionLogs->get(1)->update(['purpose' => Purpose::Sales]);

        $this->json($this->method, $this->endpoint, ['filter' => ['purposes' => [Purpose::Sales]]])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->productionLogs->get(1)->id);
    }

    /**
     * Debe buscar registros por varios IDs de empleados.
     *
     * @test
     */
    public function shouldSearchByManyEmployeeIDs()
    {
        factory(ProductionLog::class)->create(['employee_id' => $employeeID1 = factory(Employee::class)->create()->id]);
        factory(ProductionLog::class)->create(['employee_id' => $employeeID2 = factory(Employee::class)->create()->id]);

        $this->json($this->method, $this->endpoint, ['filter' => [
            'employee_ids' => [$employeeID1, $employeeID2],
        ]])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.employee_id', $employeeID2)
            ->assertJsonPath('data.1.employee_id', $employeeID1);
    }

    /**
     * Debe buscar registros por varios IDs de productos.
     *
     * @test
     */
    public function shouldSearchByManyProductIDs()
    {
        factory(ProductionLog::class)->create(['product_id' => $productID1 = factory(Product::class)->create()->id]);
        factory(ProductionLog::class)->create(['product_id' => $productID2 = factory(Product::class)->create()->id]);

        $this->json($this->method, $this->endpoint, ['filter' => [
            'product_ids' => [$productID1, $productID2],
        ]])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.product_id', $productID2)
            ->assertJsonPath('data.1.product_id', $productID1);
    }

    /**
     * Debe buscar registros por varios IDs de máquinas.
     *
     * @test
     */
    public function shouldSearchByManyMachineIDs()
    {
        factory(ProductionLog::class)->create(['machine_id' => $machineID1 = factory(Machine::class)->create()->id]);
        factory(ProductionLog::class)->create(['machine_id' => $machineID2 = factory(Machine::class)->create()->id]);

        $this->json($this->method, $this->endpoint, ['filter' => [
            'machine_ids' => [$machineID1, $machineID2],
        ]])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.machine_id', $machineID2)
            ->assertJsonPath('data.1.machine_id', $machineID1);
    }

    /**
     * Debe buscar registros por varios IDs de centro de costo de máquinas.
     *
     * @test
     */
    public function shouldSearchByManyCostCenterIDs()
    {
        factory(ProductionLog::class)->create(['machine_id' => factory(Machine::class)->create([
            'id' => 123,
            'sub_cost_center_id' => $subCostCenter1 = factory(SubCostCenter::class)->create(['id' => 123]),
        ])->id]);

        factory(ProductionLog::class)->create(['machine_id' => factory(Machine::class)->create([
            'id' => 456,
            'sub_cost_center_id' => $subCostCenter2 = factory(SubCostCenter::class)->create(['id' => 456]),
        ])->id]);

        $this->json($this->method, $this->endpoint, ['filter' => [
            'cost_center_ids' => [$subCostCenter1->cost_center_id, $subCostCenter2->cost_center_id],
        ]])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.machine_id', 456)
            ->assertJsonPath('data.1.machine_id', 123);
    }

    /**
     * @test
     */
    public function shouldReturnForbiddenWhenUserDoesNotHavePermissions()
    {
        $this->user->permissions()->delete();

        $this->json($this->method, $this->endpoint, [])->assertForbidden();
    }
}
