<?php

namespace ClockTime;

use Illuminate\Support\Facades\Artisan;
use Kirby\Company\Models\SubCostCenter;
use Kirby\Employees\Models\Identification;
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
        $timeClockLog = factory(TimeClockLog::class)->make([
            'sub_cost_center_id' => factory(SubCostCenter::class)->create()->id,
        ]);
        $timeClockLog->employee->identifications()->createMany(factory(Identification::class, 2)->make()->toArray());

        $timeClockLogData = $timeClockLog->toArray();
        $timeClockLogData['checked_in_at'] = $timeClockLog->checked_in_at->toISOString();
        $timeClockLogData['checked_out_at'] = $timeClockLog->checked_out_at->toISOString();
        unset(
            $timeClockLogData['checked_in_by_id'],
            $timeClockLogData['checked_out_by_id'],
            $timeClockLogData['expected_check_in_at'],
            $timeClockLogData['expected_check_out_at'],
            $timeClockLogData['employee'],
        );

        $I->sendPOST($this->endpoint, $timeClockLogData);

        $timeClockLogData['checked_in_at'] = $timeClockLog->checked_in_at->toDateTimeString();
        $timeClockLogData['checked_out_at'] = $timeClockLog->checked_out_at->toDateTimeString();

        $I->seeResponseCodeIs(201);
        $I->seeRecord('time_clock_logs', $timeClockLogData);
        $I->seeEventTriggered(CheckedOutEvent::class);
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
