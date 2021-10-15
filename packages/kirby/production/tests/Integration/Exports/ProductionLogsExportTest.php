<?php

namespace kirby\Production\Tests\Integration\Exports;

use Kirby\Employees\Models\Employee;
use Kirby\Machines\Models\Machine;
use Kirby\Production\Exports\ProductionLogsExport;
use Kirby\Production\Models\ProductionLog;
use Kirby\Products\Models\Product;
use ProductionPackageSeed;
use Tests\TestCase;

class ProductionLogsExportTest extends TestCase
{
    /**
     * @var \Kirby\Employees\Models\Employee
     */
    private $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ProductionPackageSeed::class);
        $this->employee = factory(Employee::class)->create();
        $this->productionLogs = factory(ProductionLog::class, 5)->create([
            'employee_id' => $this->employee,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        // registros de producción muy antiguos
        factory(ProductionLog::class, 5)->create([
            'created_at' => now()->subDays(45),
            'updated_at' => now()->subDays(45),
        ]);
    }

    /**
     * Debe exportar todas los registros si no hay filtros especificados.
     *
     * @test
     */
    public function shouldExportAllRowsWhenFilterParamsAreEmpty()
    {
        $export = new ProductionLogsExport([]);

        $this->assertCount(ProductionLog::count(), $export->query()->get());
        $this->assertEqualsCanonicalizing(ProductionLog::pluck('id'), $export->query()->pluck('id'));
    }

    /**
     * Debe exportar registros por id de empleado.
     *
     * @test
     */
    public function shouldExportByEmployeeId()
    {
        $export = new ProductionLogsExport(['employee_id' => $this->employee->id]);

        $this->assertCount($this->productionLogs->count(), $export->query()->get());
        $this->assertEqualsCanonicalizing($this->productionLogs->pluck('id'), $export->query()->pluck('id'));
    }

    /**
     * Debe exportar registros por id de máquina.
     *
     * @test
     */
    public function shouldExportByMachineId()
    {
        $logs = factory(ProductionLog::class, 2)->create([
            'machine_id' => $machine = factory(Machine::class)->create(),
        ]);

        $export = new ProductionLogsExport(['machine_id' => $machine->id]);

        $this->assertCount($logs->count(), $export->query()->get());
        $this->assertEqualsCanonicalizing($logs->pluck('id'), $export->query()->pluck('id'));
    }

    /**
     * Debe exportar registros por id de producto.
     *
     * @test
     */
    public function shouldExportByProductId()
    {
        $logs = factory(ProductionLog::class, 2)->create([
            'product_id' => $product = factory(Product::class)->create(),
        ]);

        $export = new ProductionLogsExport(['product_id' => $product->id]);

        $this->assertCount($logs->count(), $export->query()->get());
        $this->assertEqualsCanonicalizing($logs->pluck('id'), $export->query()->pluck('id'));
    }

    /**
     * Debe exportar registros por día de creación de registro.
     *
     * @test
     */
    public function shouldExportByCreationDate()
    {
        $export = new ProductionLogsExport(['creation_date' => [
            'start' => now()->subDays(2)->startOfDay()->toISOString(),
            'end' => now()->subDays(2)->endOfDay()->toISOString(),
        ]]);

        $this->assertCount($this->productionLogs->count(), $export->query()->get());
        $this->assertEqualsCanonicalizing($this->productionLogs->pluck('id'), $export->query()->pluck('id'));
    }

    /**
     * Debe exportar registros dado un peso neto específico.
     *
     * @test
     */
    public function shouldExportByNetWeight()
    {
        $logs = factory(ProductionLog::class, 2)->create([
            'tare_weight' => 5.05,
            'gross_weight' => 123.75,
        ]);

        $export = new ProductionLogsExport(['net_weight' => 123.75 - 5.05]);

        $this->assertCount($logs->count(), $export->query()->get());
        $this->assertEqualsCanonicalizing($logs->pluck('id'), $export->query()->pluck('id'));
    }
}
