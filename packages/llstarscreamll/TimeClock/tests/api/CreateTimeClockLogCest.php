<?php
namespace ClockTime;

use ClockTime\ApiTester;
use Illuminate\Support\Carbon;
use llstarscreamll\Users\Models\User;

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
     * @var \llstarscreamll\Users\Models\User
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
    public function shouldRegisterEmployeeClockIn(ApiTester $I)
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 07));

        $employee = factory(User::class)
            ->with('roles', ['name' => 'employee'])
            ->with('identifications', ['name' => 'card', 'code' => 'some-employee-card-code'])
            ->with('workShifts', ['name' => '7 a 6', 'start_time' => '07:00', 'end_time' => '18:00'])
            ->create();

        $requestData = [
            'action' => 'check_in',
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(201);
        $I->seeResponseJsonMatchesJsonPath("$.data.id");
        $I->seeRecord('time_clock_logs', [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->first()->id,
            'checked_in_at' => now()->toDateTimeString(),
            'checked_in_by_id' => $this->user->id,
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldRegisterEmployeeClockOut(ApiTester $I)
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 07));
        $checkedInTime = now()->setTime(7, 0);

        $employee = factory(User::class)
            ->with('roles', ['name' => 'employee'])
            ->with('identifications', ['name' => 'card', 'code' => 'some-employee-card-code'])
            ->with('workShifts', ['name' => '7 a 6', 'start_time' => '07:00', 'end_time' => '18:00'])
            ->with('timeClockLogs', [
                'work_shift_id' => 1,
                'checked_in_at' => $checkedInTime,
                'checked_in_by_id' => $this->user->id,
            ])
            ->create();

        $requestData = [
            'action' => 'check_out',
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath("$.data.id");
        $I->seeRecord('time_clock_logs', [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->first()->id,
            'checked_in_at' => $checkedInTime->toDateTimeString(),
            'checked_out_at' => now()->toDateTimeString(),
            'checked_in_by_id' => $this->user->id,
            'checked_out_by_id' => $this->user->id,
        ]);
    }
}
