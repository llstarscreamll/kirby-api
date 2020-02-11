<?php

namespace ClockTime;

use Illuminate\Support\Facades\Artisan;
use Kirby\TimeClock\Events\CheckedOutEvent;
use Kirby\TimeClock\Models\TimeClockLog;
use TimeClockPermissionsSeeder;

/**
 * Class CreateTimeClockLogCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateTimeClockLogCest
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

        $I->haveHttpHeader('Accept', 'application/json');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldCreateResourceSuccessful(ApiTester $I)
    {
        $timeClockLogData = factory(TimeClockLog::class)->make()->toArray();

        $I->sendPOST($this->endpoint, $timeClockLogData);

        $I->seeResponseCodeIs(201);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeEventTriggered(CheckedOutEvent::class);
        $I->seeRecord('time_clock_logs', $timeClockLogData);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnUnathorizedIfUserDoesntHaveRequiredPermission(ApiTester $I)
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();

        $I->sendPOST($this->endpoint, []);

        $I->seeResponseCodeIs(403);
    }
}
