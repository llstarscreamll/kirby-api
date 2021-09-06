<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Kirby\Employees\Models\Employee;
use Kirby\Production\Exports\ProductionLogsExport;
use Kirby\Production\Jobs\ExportProductionLogsToCsvJob;
use Kirby\Production\Models\ProductionLog;
use Kirby\Production\Notifications\ProductionLogsCsvReady;
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
     * @test
     */
    public function shouldBuildCsvWithProductionLogDataAndDispatchNotification()
    {
        $params = [
            'from' => now()->subDays(5)->toISOString(),
            'to' => now()->toISOString(),
        ];

        Excel::fake();
        Notification::fake();
        Carbon::setTestNow('2020-01-20 10:20:30');

        ExportProductionLogsToCsvJob::dispatch($this->employee->user, $params);

        $expectedFile = 'production-logs/exports/2020-01-20-10-20-30-production-logs.csv';

        Excel::assertStored($expectedFile, 'public', function (ProductionLogsExport $export) {
            $this->assertCount($this->productionLogs->count(), $export->query()->get());

            return true;
        });

        Notification::assertSentTo($this->employee->user, ProductionLogsCsvReady::class);
    }
}
