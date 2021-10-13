<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Kirby\Employees\Models\Employee;
use Kirby\Production\Exports\ProductionLogsExport;
use Kirby\Production\Jobs\ExportProductionLogsToCsvJob;
use Kirby\Production\Models\ProductionLog;
use Kirby\Production\Notifications\ProductionLogsCsvReady;
use Kirby\Users\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ExportProductionLogsToCsvJobTest extends TestCase
{
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
     * Debe enviar al exportador de datos el array que contiene los filtros de
     * datos.
     *
     * @test
     */
    public function shouldPassFilterParamsToExporterClass()
    {
        $params = ['foo' => '111', 'bar' => '222'];

        Excel::fake();
        Excel::shouldReceive('store')
            ->with(Mockery::on(function ($arg) use ($params) {
                $this->assertInstanceOf(ProductionLogsExport::class, $arg);
                $this->assertEquals($params, $arg->params);

                return true;
            }), Mockery::any(), Mockery::any());

        ExportProductionLogsToCsvJob::dispatch($this->employee->user, $params);
    }

    /**
     * Debe generar archivo con cierto nombre en cierto disco.
     *
     * @test
     */
    public function shouldGenerateFileWithSpecificNameAndStoreOnSpecificDisk()
    {
        Excel::fake();
        Carbon::setTestNow('2020-01-20 10:20:30');

        ExportProductionLogsToCsvJob::dispatch($this->employee->user, []);

        $expectedFile = 'production-logs/exports/2020-01-20-10-20-30-production-logs.csv';

        Excel::assertStored($expectedFile, 'public');
    }

    /**
     * Debe enviar notificación al usuario especificado.
     *
     * @test
     */
    public function shouldSendNotificationToSpecifiedUser()
    {
        Excel::fake();
        Notification::fake();

        ExportProductionLogsToCsvJob::dispatch($this->employee->user, []);

        Notification::assertSentTo($this->employee->user, ProductionLogsCsvReady::class);
    }
}
