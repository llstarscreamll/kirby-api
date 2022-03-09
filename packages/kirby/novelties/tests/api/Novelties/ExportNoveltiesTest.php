<?php

namespace Kirby\Novelties\Tests\api\Novelties;

use Illuminate\Support\Facades\Queue;
use Kirby\Employees\Models\Employee;
use Kirby\Novelties\Jobs\GenerateCsvReportByEmployeeJob;
use NoveltiesPackageSeed;

/**
 * Class ExportNoveltiesTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 *
 * @internal
 */
class ExportNoveltiesTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/novelties/export';

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
            'time_clock_log_check_out_start_date' => now()->startOfMonth()->toDateTimeString(),
            'time_clock_log_check_out_end_date' => now()->endOfMonth()->toDateTimeString(),
        ];

        $this->json('POST', $this->endpoint, $requestData)
            ->assertOk()
            ->assertJsonFragment(['data' => 'ok']);

        Queue::assertPushed(
            GenerateCsvReportByEmployeeJob::class,
            fn ($job) => $job->params === $requestData && $job->userId = $this->user->id
        );
    }

    /**
     * @test
     */
    public function shouldReturnForbiddenWhenUserDoesntHaveRequiredPermissions()
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();

        $this->json('POST', $this->endpoint, [])
            ->assertForbidden();
    }
}
