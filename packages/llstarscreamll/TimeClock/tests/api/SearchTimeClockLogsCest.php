<?php

namespace ClockTime;

use TimeClockPermissionsSeeder;
use Illuminate\Support\Facades\Artisan;
use llstarscreamll\TimeClock\Models\TimeClockLog;

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
     * @var \llstarscreamll\Users\Models\User
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
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnUnathorizedIfUserDoesntHaveRequiredPermission(ApiTester $I)
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();

        $I->sendGET($this->endpoint, []);

        $I->seeResponseCodeIs(403);
    }
}
