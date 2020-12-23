<?php

namespace Kirby\Novelties\Tests\api\Novelties;

use Illuminate\Support\Facades\Queue;
use Kirby\Employees\Models\Employee;
use Kirby\Novelties\Jobs\GenerateCsvEmployeeResumeByNoveltyTypeJob;
use NoveltiesPackageSeed;
use Tests\TestCase;

/**
 * Class ExportEmployeesResumeByNoveltyTypeTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class ExportEmployeesResumeByNoveltyTypeTest extends TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/novelties/export-resume-by-novelty-type';

    /**
     * @var \Kirby\Users\Models\User
     */
    private $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->seed(NoveltiesPackageSeed::class);
        $this->actingAsAdmin($this->user = factory(\Kirby\Users\Models\User::class)->create());
    }

    /**
     * @test
     */
    public function updateNoveltySuccessfully()
    {
        Queue::fake();
        $employee = factory(Employee::class)->create();
        $requestData = [
            'employee_id' => $employee->id,
            'start_at' => now()->startOfMonth()->toISOString(),
            'end_at' => now()->endOfMonth()->toISOString(),
        ];

        $this->json('GET', $this->endpoint, $requestData)
            ->assertOk()
            ->assertJsonFragment(['data' => 'ok']);

        Queue::assertPushed(
            GenerateCsvEmployeeResumeByNoveltyTypeJob::class,
            fn ($job) => $job->makeReportData->employeeId === $employee->id &&
                $job->makeReportData->startDate == now()->startOfMonth() &&
                $job->makeReportData->endDate == now()->endOfMonth()
        );
    }

    /**
     * @test
     */
    public function shouldReturnForbidenWhenUserDoesntHaveRequiredPermissions()
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();

        $this->json('GET', $this->endpoint, [])
            ->assertForbidden();
    }
}
