<?php

namespace ClockTime;

use Illuminate\Support\Facades\Artisan;
use Kirby\TimeClock\Actions\GenerateReportByEmployee;
use Mockery;
use TimeClockPermissionsSeeder;

/**
 * Class ReportByEmployeeCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class ReportByEmployeeCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/time-clock/report-by-employee/{employee_id}';

    /**
     * @var \Kirby\Users\Models\User
     */
    private $user;

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        $I->disableMiddleware();

        Artisan::call('db:seed', ['--class' => TimeClockPermissionsSeeder::class]);
        $this->user = $I->amLoggedAsAdminUser();

        $I->haveHttpHeader('Accept', 'application/json');
    }

    /**
     * @param ApiTester $I
     */
    public function shouldReturnReportData(ApiTester $I)
    {
        $employeeId = 10;
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();
        $expectedResponse = [
            [
                'date' => $startDate->toDateString(),
                'employee_identification_number' => '123456',
                'sub_cost_centers' => [
                    ['id' => 1, 'code' => 'scc-1', 'name' => 'SCC 1'],
                ],
                'novelties' => [
                    [
                        'id' => 2,
                        'novelty_type' => 'NT1',
                        'total_time_in_minutes' => 100,
                    ],
                ],
                'novelties_time_sum' => 100,
                'novelties_comments_count' => 1,
                'novelties_approvers' => 0,
            ],
        ];

        $actionMock = Mockery::mock(GenerateReportByEmployee::class)
            ->shouldReceive('run')
            ->withArgs(fn($arg1, $arg2, $arg3) => $arg1 === $employeeId && $startDate->isSameAs($arg2) && $endDate->isSameAs($arg3))
            ->andReturn($expectedResponse)
            ->getMock();

        $I->haveInstance(GenerateReportByEmployee::class, $actionMock);

        $endpoint = str_replace('{employee_id}', $employeeId, $this->endpoint);
        $I->sendGET($endpoint, ['start_date' => $startDate->toISOString(), 'end_date' => $endDate->toISOString()]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.0.date');
        $I->seeResponseJsonMatchesJsonPath('$.data.0.employee_identification_number');
        $I->seeResponseJsonMatchesJsonPath('$.data.0.sub_cost_centers.0.id');
        $I->seeResponseJsonMatchesJsonPath('$.data.0.sub_cost_centers.0.code');
        $I->seeResponseJsonMatchesJsonPath('$.data.0.sub_cost_centers.0.name');
        $I->seeResponseJsonMatchesJsonPath('$.data.0.novelties.0.id');
        $I->seeResponseJsonMatchesJsonPath('$.data.0.novelties.0.novelty_type');
        $I->seeResponseJsonMatchesJsonPath('$.data.0.novelties.0.total_time_in_minutes');
        $I->seeResponseJsonMatchesJsonPath('$.data.0.novelties_time_sum');
        $I->seeResponseJsonMatchesJsonPath('$.data.0.novelties_comments_count');
        $I->seeResponseJsonMatchesJsonPath('$.data.0.novelties_approvers');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnUnathorizedIfUserDoesntHaveRequiredPermission(ApiTester $I)
    {
        $employeeId = 10;
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();

        $this->user->roles()->delete();
        $this->user->permissions()->delete();

        $endpoint = str_replace('{employee_id}', $employeeId, $this->endpoint);
        $I->sendGET($endpoint, ['start_date' => $startDate->toISOString(), 'end_date' => $endDate->toISOString()]);

        $I->seeResponseCodeIs(403);
    }
}
