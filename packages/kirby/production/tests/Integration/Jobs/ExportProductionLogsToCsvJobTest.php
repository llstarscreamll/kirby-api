<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Kirby\Employees\Models\Employee;
use Kirby\Production\Exports\ProductionLogsExport;
use Kirby\Production\Jobs\ExportProductionLogsToCsvJob;
use Kirby\Production\Notifications\ProductionLogsCsvReady;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ExportProductionLogsToCsvJobTest extends TestCase
{
    /**
     * @var \Kirby\Employees\Models\Employee
     */
    private $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = factory(Employee::class)->create();
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
