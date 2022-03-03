<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Kirby\Employees\Models\Employee;
use Kirby\Production\Enums\Tag;
use Kirby\Production\Models\ProductionLog;
use Kirby\Products\Models\Product;
use Kirby\Users\Models\User;
use Tests\TestCase;

/**
 * @internal
 */
class ProductionReportsTest extends TestCase
{
    private string $endpoint = 'api/v1/production-reports';

    private string $method = 'GET';

    private User $user;

    private Employee $employee;

    private Collection $productionLogs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ProductionPackageSeed::class);
        $this->actingAsAdmin($this->user = factory(User::class)->create());
        $this->employee = factory(Employee::class)->create(['id' => $this->user->id]);
        $this->productionLogs = factory(ProductionLog::class, 5)->create([
            'employee_id' => $this->employee,
            'tag_updated_at' => now()->subMonth(),
            'tare_weight' => '1.5',
            'gross_weight' => '5.5',
        ]);
    }

    /**
     * Debe devolver cierta estructura de datos.
     *
     * @test
     */
    public function shouldReturnCertainDataStructure()
    {
        // dos registros de producción del mismo producto, entonces el acumulado
        // sería: (5.5 - 1.5) * 2 = 8
        DB::table('production_logs')->take(2)->update([
            'product_id' => $this->productionLogs->first()->product_id,
            'tag_updated_at' => now()->subDays(2)
        ]);

        $this->json($this->method, $this->endpoint, ['filter' => [
            'tag_updated_at' => ['start' => now()->subDays(5)->toISOString(), 'end' => now()->toISOString()],
        ]])
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'short_name', 'kgs']]])
            ->assertJsonPath('data.0.id', $this->productionLogs->first()->product_id) // ID del producto producido
            ->assertJsonPath('data.0.short_name', Product::first()->name) // nombre corto de producto producido
            ->assertJsonPath('data.0.kgs', '8.00'); // producido acumulado en Kgs del producto
    }

    /**
     * Debe entregar registros de hace 15 días de forma predeterminada si no se
     * especifica el rango de fecha de etiqueta.
     *
     * @test
     */
    public function shouldReturnDataFromLastFifteenDaysWhenNoTagDateRangeIsGiven()
    {
        // el servicio solamente debe devolver este producido pues es el único
        // dentro del rango de tiempo especificado
        DB::table('production_logs')
            ->where('id', $this->productionLogs->first()->id)
            ->update(['tag_updated_at' => now()->subDays(15)->endOfDay()]);

        $this->json($this->method, $this->endpoint)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->productionLogs->first()->product_id);
    }

    /**
     * Debe devolver lo producido dentro del rango de fecha especificado.
     *
     * @test
     */
    public function shouldReturnDataBetweenTagDateRange()
    {
        // el servicio solamente debe devolver este producido pues es el único
        // dentro del rango de tiempo especificado
        DB::table('production_logs')
            ->where('id', $this->productionLogs->first()->id)
            ->update(['tag_updated_at' => now()->subDays(2)]);

        $this->json($this->method, $this->endpoint, ['filter' => [
            'tag_updated_at' => ['start' => now()->subDays(5)->toISOString(), 'end' => now()->toISOString()],
        ]])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->productionLogs->first()->product_id);
    }

    /**
     * Debe devolver lo producido de una o varias etiquetas.
     *
     * @test
     */
    public function shouldReturnDataByManyTags()
    {
        DB::table('production_logs')->update(['tag' => Tag::Rejected, 'tag_updated_at' => now()->subDays(2)]);
        DB::table('production_logs')->where('id', $this->productionLogs->first()->id)->update(['tag' => Tag::InLine]);
        DB::table('production_logs')->where('id', $this->productionLogs->get(1)->id)->update(['tag' => Tag::Error]);

        $this->json($this->method, $this->endpoint, ['filter' => ['tags' => [Tag::Error, Tag::InLine]]])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $this->productionLogs->first()->product_id])
            ->assertJsonFragment(['id' => $this->productionLogs->get(1)->product_id]);
    }

    /**
     * Debe devolver lo producido de uno o varios empleados.
     *
     * @test
     */
    public function shouldReturnDataByManyEmployees()
    {
        DB::table('production_logs')->update(['tag_updated_at' => now()->subDays(2)]);
        $logs = factory(ProductionLog::class, 2)->create(['tag_updated_at' => now()->subDay()]);

        $this->json($this->method, $this->endpoint, ['filter' => ['employee_ids' => $logs->pluck('employee_id')]])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $logs->first()->product_id])
            ->assertJsonFragment(['id' => $logs->last()->product_id]);
    }

    /**
     * Debe devolver lo producido de uno o varios productos.
     *
     * @test
     */
    public function shouldReturnDataByManyProducts()
    {
        DB::table('production_logs')->update(['tag_updated_at' => now()->subDays(2)]);
        $logs = factory(ProductionLog::class, 2)->create(['tag_updated_at' => now()->subDay()]);

        $this->json($this->method, $this->endpoint, ['filter' => ['product_ids' => $logs->pluck('product_id')]])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $logs->first()->product_id])
            ->assertJsonFragment(['id' => $logs->last()->product_id]);
    }

    /**
     * Debe devolver lo producido de una o varias máquinas.
     *
     * @test
     */
    public function shouldReturnDataByManyMachines()
    {
        DB::table('production_logs')->update(['tag_updated_at' => now()->subDays(2)]);
        $logs = factory(ProductionLog::class, 2)->create(['tag_updated_at' => now()->subDay()]);

        $this->json($this->method, $this->endpoint, ['filter' => ['machine_ids' => $logs->pluck('machine_id')]])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $logs->first()->product_id])
            ->assertJsonFragment(['id' => $logs->last()->product_id]);
    }

    /**
     * Debe devolver lo producido de uno o varios centros de costo.
     *
     * @test
     */
    public function shouldReturnDataByManyCostCenters()
    {
        DB::table('production_logs')->update(['tag_updated_at' => now()->subDays(2)]);
        $logs = factory(ProductionLog::class, 2)->create(['tag_updated_at' => now()->subDay()]);
        $costCenterIDs = $logs->map->machine->map->subCostCenter->pluck('cost_center_id');

        $this->json($this->method, $this->endpoint, ['filter' => ['cost_center_ids' => $costCenterIDs]])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $logs->first()->product_id])
            ->assertJsonFragment(['id' => $logs->last()->product_id]);
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
