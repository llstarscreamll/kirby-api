<?php

namespace Kirby\Production\Tests\Feature\API\V1;

use Illuminate\Support\Facades\Queue;
use Kirby\Employees\Models\Employee;
use Kirby\Production\Jobs\ExportProductionLogsToCsvJob;
use Kirby\Production\Models\ProductionLog;
use Kirby\Users\Models\User;
use ProductionPackageSeed;
use Tests\TestCase;

class ExportProductionLogsToCsvTest extends TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/production-logs/export-to-csv';

    /**
     * @var string
     */
    private $method = 'POST';

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
        $this->productionLogs = factory(ProductionLog::class, 5)->create([
            'employee_id' => $this->employee,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);
    }

    /**
     * @test
     */
    public function shouldDispatchJobWhenInputDataIsOk()
    {
        Queue::fake();

        $dataInput = [
            'filter' => [
                'creation_date' => [
                    'start' => now()->subDays(5)->startOfDay()->toISOString(),
                    'end' => now()->subDays(5)->endOfDay()->toISOString(),
                ],
            ],
        ];

        $this->json($this->method, $this->endpoint, $dataInput)->assertOk();

        Queue::assertPushed(ExportProductionLogsToCsvJob::class, fn ($job) => $job->params === $dataInput['filter'] && $job->user->is($this->user));
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
