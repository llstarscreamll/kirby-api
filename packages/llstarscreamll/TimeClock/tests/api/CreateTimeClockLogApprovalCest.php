<?php

namespace ClockTime;

use Illuminate\Support\Facades\Artisan;
use llstarscreamll\TimeClock\Events\TimeClockLogApprovalCreatedEvent;
use llstarscreamll\TimeClock\Models\TimeClockLog;
use TimeClockPermissionsSeeder;

/**
 * Class CreateTimeClockLogApprovalCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateTimeClockLogApprovalCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/time-clock-logs/{time-clock-log-id}/approvals';

    /**
     * @var \llstarscreamll\Users\Models\User
     */
    private $user;

    /**
     * @var \Illuminate\Support\Collection
     */
    private $timeClockLogs;

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        $I->disableMiddleware();

        Artisan::call('db:seed', ['--class' => TimeClockPermissionsSeeder::class]);
        $this->user = $I->amLoggedAsAdminUser();
        $this->timeClockLogs = factory(TimeClockLog::class, 2)->create();

        $I->haveHttpHeader('Accept', 'application/json');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldSetApprovalSuccessfully(ApiTester $I)
    {
        $timeClockLog = $this->timeClockLogs->first();
        $endpoint = str_replace('{time-clock-log-id}', $timeClockLog->id, $this->endpoint);
        $I->sendPOST($endpoint);

        $I->seeResponseCodeIs(201);
        $I->seeEventTriggered(TimeClockLogApprovalCreatedEvent::class);
        $I->seeRecord('time_clock_log_approvals', [
            'user_id' => $this->user->id,
            'time_clock_log_id' => $timeClockLog->id,
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnUnathorizedIfUserDoesntHaveRequiredPermission(ApiTester $I)
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();

        $endpoint = str_replace('{time-clock-log-id}', $this->timeClockLogs->first()->id, $this->endpoint);
        $I->sendPOST($endpoint);

        $I->seeResponseCodeIs(403);
        $I->dontSeeRecord('time_clock_log_approvals', [
            'user_id' => $this->user->id,
            'time_clock_log_id' => $this->timeClockLogs->first()->id,
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnNotFoundIfTimeClockLogDoesntExists(ApiTester $I)
    {
        $endpoint = str_replace('{time-clock-log-id}', 111, $this->endpoint);
        $I->sendPOST($endpoint);

        $I->seeResponseCodeIs(404);
    }
}
