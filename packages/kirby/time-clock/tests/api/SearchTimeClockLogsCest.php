<?php

namespace ClockTime;

use Illuminate\Support\Facades\Artisan;
use Kirby\TimeClock\Models\TimeClockLog;
use TimeClockPermissionsSeeder;

/**
 * Class SearchTimeClockLogsCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SearchTimeClockLogsCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/time-clock-logs';

    /**
     * @var \Kirby\Users\Models\User
     */
    private $user;

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        Artisan::call('db:seed', ['--class' => TimeClockPermissionsSeeder::class]);
        $this->user = $I->amLoggedAsAdminUser();
        // time clock logs
        factory(TimeClockLog::class, 2)->create();

        $I->haveHttpHeader('Accept', 'application/json');
    }

    /**
     * @param ApiTester $I
     */
    public function shouldReturnPaginatedData(ApiTester $I)
    {
        $I->sendGET($this->endpoint);
        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.0');
        $I->seeResponseJsonMatchesJsonPath('$.data.1');
        // relations
        $I->seeResponseJsonMatchesJsonPath('$.data.1.novelties');
        $I->seeResponseJsonMatchesJsonPath('$.data.1.employee.user');
        $I->seeResponseJsonMatchesJsonPath('$.data.1.work_shift');
        $I->seeResponseJsonMatchesJsonPath('$.data.1.approvals');
        $I->seeResponseJsonMatchesJsonPath('$.data.1.sub_cost_center');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnForbidenWhenUserDoesntHaveRequiredPermissions(ApiTester $I)
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();

        $I->sendGET($this->endpoint, []);

        $I->seeResponseCodeIs(403);
    }
}
