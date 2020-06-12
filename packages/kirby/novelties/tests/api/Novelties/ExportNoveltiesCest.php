<?php

namespace Novelties;

use Illuminate\Support\Facades\Queue;
use Kirby\Employees\Models\Employee;
use Kirby\Novelties\Jobs\GenerateCsvReportByEmployeeJob;

/**
 * Class ExportNoveltiesCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class ExportNoveltiesCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/novelties/export';

    /**
     * @var \Kirby\Users\Models\User
     */
    private $user;

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        $this->user = $I->amLoggedAsAdminUser();
        $I->haveHttpHeader('Accept', 'application/json');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function updateNoveltySuccessfully(ApiTester $I)
    {
        Queue::fake();
        $employee = factory(Employee::class)->create();
        $requestData = [
            'employee_id' => $employee->id,
            'time_clock_log_check_out_start_date' => now()->startOfMonth()->toDateTimeString(),
            'time_clock_log_check_out_end_date' => now()->endOfMonth()->toDateTimeString(),
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['data' => 'ok']);

        Queue::assertPushed(
            GenerateCsvReportByEmployeeJob::class,
            fn ($job) => $job->params === $requestData && $job->userId = $this->user->id
        );
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnUnprocesableEntityIfUserDoesntHaveRequiredPermissions(ApiTester $I)
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();

        $I->sendPOST($this->endpoint, []);

        $I->seeResponseCodeIs(403);
    }
}
