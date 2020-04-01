<?php

namespace Novelties;

use Illuminate\Support\Facades\Artisan;
use Kirby\Company\Models\SubCostCenter;
use Kirby\Novelties\Actions\GenerateReportByEmployee;
use Kirby\Novelties\Models\Novelty;
use Kirby\Users\Models\User;
use Mockery;
use NoveltiesPermissionsSeeder;

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
    private $endpoint = 'api/v1/novelties/report-by-employee/{employee_id}';

    /**
     * @var \Kirby\Users\Models\User
     */
    private $user;

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        Artisan::call('db:seed', ['--class' => NoveltiesPermissionsSeeder::class]);
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
        $approver = factory(User::class)->create();
        $novelty = factory(Novelty::class)->create([
            'sub_cost_center_id' => factory(SubCostCenter::class)->create()->id,
            'scheduled_start_at' => $startDate,
            'scheduled_end_at' => $endDate,
        ]);

        $novelty->approvals()->sync([$approver->id]);

        $expectedResponse = [
            [
                'date' => $startDate->toDateString(),
                'employee' => $novelty->employee->toArray(),
                'novelties' => [$novelty->load(['employee', 'subCostCenter.costCenter', 'approvals', 'noveltyType'])->toArray()],
            ],
        ];

        $actionMock = Mockery::mock(GenerateReportByEmployee::class)
            ->shouldReceive('run')
            ->withArgs(fn ($arg1, $arg2, $arg3) => $arg1 === $employeeId && $startDate->isSameAs($arg2) && $endDate->isSameAs($arg3))
            ->andReturn($expectedResponse)
            ->getMock();

        $I->haveInstance(GenerateReportByEmployee::class, $actionMock);

        $endpoint = str_replace('{employee_id}', $employeeId, $this->endpoint);
        $I->sendGET($endpoint, ['start_date' => $startDate->toISOString(), 'end_date' => $endDate->toISOString()]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.0.date');
        // novelty attributes
        $I->seeResponseJsonMatchesJsonPath('$.data.0.novelties.0.id');
        $I->seeResponseJsonMatchesJsonPath('$.data.0.novelties.0.scheduled_start_at');
        $I->seeResponseJsonMatchesJsonPath('$.data.0.novelties.0.scheduled_end_at');
        $I->seeResponseJsonMatchesJsonPath('$.data.0.novelties.0.total_time_in_minutes');
        $I->seeResponseJsonMatchesJsonPath('$.data.0.novelties.0.comment');
        // novelty type data
        $I->seeResponseJsonMatchesJsonPath('$.data.0.novelties.0.novelty_type.id');
        $I->seeResponseJsonMatchesJsonPath('$.data.0.novelties.0.novelty_type.code');
        $I->seeResponseJsonMatchesJsonPath('$.data.0.novelties.0.novelty_type.name');
        // employee data
        $I->seeResponseJsonMatchesJsonPath('$.data.0.employee.id');
        $I->seeResponseJsonMatchesJsonPath('$.data.0.employee.code');
        $I->seeResponseJsonMatchesJsonPath('$.data.0.employee.user.first_name');
        $I->seeResponseJsonMatchesJsonPath('$.data.0.employee.user.last_name');
        $I->seeResponseJsonMatchesJsonPath('$.data.0.employee.identification_number');
        // sub cost center data
        $I->seeResponseJsonMatchesJsonPath('$.data.0.novelties.0.sub_cost_center.id');
        $I->seeResponseJsonMatchesJsonPath('$.data.0.novelties.0.sub_cost_center.code');
        $I->seeResponseJsonMatchesJsonPath('$.data.0.novelties.0.sub_cost_center.name');
        // cost center data
        $I->seeResponseJsonMatchesJsonPath('$.data.0.novelties.0.sub_cost_center.cost_center.id');
        $I->seeResponseJsonMatchesJsonPath('$.data.0.novelties.0.sub_cost_center.cost_center.code');
        $I->seeResponseJsonMatchesJsonPath('$.data.0.novelties.0.sub_cost_center.cost_center.name');
        // approvals data
        $I->seeResponseJsonMatchesJsonPath('$.data.0.novelties.0.approvals.0.id');
        $I->seeResponseJsonMatchesJsonPath('$.data.0.novelties.0.approvals.0.first_name');
        $I->seeResponseJsonMatchesJsonPath('$.data.0.novelties.0.approvals.0.last_name');
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
