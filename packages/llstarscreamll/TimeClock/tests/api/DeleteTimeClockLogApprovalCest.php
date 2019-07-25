<?php

namespace ClockTime;

use TimeClockPermissionsSeeder;
use Illuminate\Support\Facades\Artisan;
use llstarscreamll\TimeClock\Models\TimeClockLog;

/**
 * Class DeleteTimeClockLogApprovalCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class DeleteTimeClockLogApprovalCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/time-clock-logs/{time-clock-log-id}/approvals/{approval-id}';

    /**
     * @var \llstarscreamll\Users\Models\User
     */
    private $user;

    /**
     * @var \Illuminate\Support\Collection
     */
    private $timeClockLogs;

    /**
     * @var string
     */
    private $approvalId;

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        $I->disableMiddleware();

        Artisan::call('db:seed', ['--class' => TimeClockPermissionsSeeder::class]);
        $this->user = $I->amLoggedAsAdminUser();
        $this->timeClockLogs = factory(TimeClockLog::class, 2)->create();
        $this->approvalId = $I->haveRecord('time_clock_log_approvals', [
            'user_id' => $this->user->id,
            'time_clock_log_id' => $this->timeClockLogs->first()->id,
        ]);

        $I->haveHttpHeader('Accept', 'application/json');
    }

    /**
     * @param ApiTester $I
     */
    public function shouldDeleteApprovalSuccessfully(ApiTester $I)
    {
        $endpoint = str_replace(
            ['{time-clock-log-id}', '{approval-id}'],
            [$this->timeClockLogs->first()->id, $this->approvalId],
            $this->endpoint
        );
        $I->sendDELETE($endpoint);

        $I->seeResponseCodeIs(200);
        $I->dontSeeRecord('time_clock_log_approvals', [
            'user_id' => $this->user->id,
            'time_clock_log_id' => $this->timeClockLogs->first()->id,
        ]);
    }

    /**
     * @param ApiTester $I
     */
    public function shouldReturnUnathorizedIfUserDoesntHaveRequiredPermission(ApiTester $I)
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();

        $endpoint = str_replace(
            ['{time-clock-log-id}', '{approval-id}'],
            [$this->timeClockLogs->first()->id, $this->approvalId],
            $this->endpoint
        );
        $I->sendDELETE($endpoint);

        $I->seeResponseCodeIs(403);
        $I->seeRecord('time_clock_log_approvals', [
            'user_id' => $this->user->id,
            'time_clock_log_id' => $this->timeClockLogs->first()->id,
        ]);
    }

    /**
     * @param ApiTester $I
     */
    public function shouldReturnNotFoundIfTimeClockLogDoesntExists(ApiTester $I)
    {
        $endpoint = str_replace('{time-clock-log-id}', 111, $this->endpoint);
        $I->sendDELETE($endpoint);

        $I->seeResponseCodeIs(404);
    }
}
