<?php

namespace ClockTime;

use Illuminate\Support\Carbon;
use llstarscreamll\Employees\Models\Employee;

/**
 * Class CheckOutCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CheckOutCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/time-clock/check-out';

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
    public function whenCheckInHasShiftAndLeavesOnTimeThenReturnOk(ApiTester $I)
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 18, 00));
        $checkedInTime = now()->setTime(7, 0);

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', ['name' => '7 to 6', 'time_slots' => [['start' => '07:00', 'end' => '18:00']]])
            ->with('timeClockLogs', [
                'work_shift_id' => 1,
                'checked_in_at' => $checkedInTime,
                'checked_in_by_id' => $this->user->id,
            ])
            ->create();

        $requestData = [
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('time_clock_logs', [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->first()->id,
            'checked_in_at' => $checkedInTime->toDateTimeString(),
            'checked_out_at' => now()->toDateTimeString(),
            'checked_in_by_id' => $this->user->id,
            'checked_out_by_id' => $this->user->id,
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenCheckInHasEmptyShift(ApiTester $I)
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 18, 00));
        $checkedInTime = now()->setTime(7, 0);

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('timeClockLogs', [
                'work_shift_id' => null, // empty shift
                'checked_in_at' => $checkedInTime,
                'checked_in_by_id' => $this->user->id,
            ])
            ->create();

        $requestData = [
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('time_clock_logs', [
            'employee_id' => $employee->id,
            'work_shift_id' => null,
            'checked_in_at' => $checkedInTime->toDateTimeString(),
            'checked_out_at' => now()->toDateTimeString(),
            'checked_in_by_id' => $this->user->id,
            'checked_out_by_id' => $this->user->id,
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenEmployeeHasNotCheckIn(ApiTester $I)
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 18, 00));
        $checkedInTime = now()->setTime(7, 0);

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->create();

        $requestData = [
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(422);
        $I->seeResponseJsonMatchesJsonPath('$.errors.code');
        $I->seeResponseJsonMatchesJsonPath('$.errors.title');
        $I->seeResponseJsonMatchesJsonPath('$.errors.detail');
    }
}
