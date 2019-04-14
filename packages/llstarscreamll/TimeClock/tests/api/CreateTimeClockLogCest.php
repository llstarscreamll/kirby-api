<?php

namespace ClockTime;

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
    public function testToCheckInWhenEmployeeIdentificationDoesNotExists(ApiTester $I)
    {
        $employee = factory(User::class)
            ->with('roles', ['name' => 'employee'])
            ->with('identifications', ['name' => 'card', 'code' => 'some-employee-card-code'])
            ->create();

        $requestData = [
            'action' => 'check_in',
            'identification_code' => 'wrong_identification_here',
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(422);
        $I->seeResponseJsonMatchesJsonPath('$.message');
        $I->seeResponseJsonMatchesJsonPath('$.errors.identification_code');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function testToCheckInWhenInvalidActionTypeGiven(ApiTester $I)
    {
        $employee = factory(User::class)
            ->with('roles', ['name' => 'employee'])
            ->with('identifications', ['name' => 'card', 'code' => 'some-employee-card-code'])
            ->create();

        $requestData = [
            'action' => 'invalid_option_here',
            'identification_code' => 'some-employee-card-code',
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(422);
        $I->seeResponseJsonMatchesJsonPath('$.message');
        $I->seeResponseJsonMatchesJsonPath('$.errors.action');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function testToCheckInWhenEmployeeHasSingleWorkShiftAndArrivesOnTime(ApiTester $I)
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 07, 00));

        $employee = factory(User::class)
            ->with('roles', ['name' => 'employee'])
            ->with('identifications', ['name' => 'card', 'code' => 'some-employee-card-code'])
            ->with('workShifts', ['name' => '7 to 6', 'start_time' => '07:00', 'end_time' => '18:00'])
            ->create();

        $requestData = [
            'action' => 'check_in',
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(201);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
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
    public function testToCheckOutWhenEmployeeHasSingleWorkShiftAndLeavesOnTime(ApiTester $I)
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 18, 00));
        $checkedInTime = now()->setTime(7, 0);

        $employee = factory(User::class)
            ->with('roles', ['name' => 'employee'])
            ->with('identifications', ['name' => 'card', 'code' => 'some-employee-card-code'])
            ->with('workShifts', ['name' => '7 to 6', 'start_time' => '07:00', 'end_time' => '18:00'])
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
    public function testToCheckInWhenEmployeeHasNotWorkShift(ApiTester $I)
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 07, 00));

        $employee = factory(User::class)
            ->with('roles', ['name' => 'employee'])
            ->with('identifications', ['name' => 'card', 'code' => 'some-employee-card-code'])
            ->create();

        $requestData = [
            'action' => 'check_in',
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(201);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('time_clock_logs', [
            'employee_id' => $employee->id,
            'work_shift_id' => null,
            'checked_in_at' => now()->toDateTimeString(),
            'checked_in_by_id' => $this->user->id,
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function testToCheckOutWhenEmployeeHasNotWorkShift(ApiTester $I)
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 18, 00));
        $checkedInTime = now()->setTime(7, 0);

        $employee = factory(User::class)
            ->with('roles', ['name' => 'employee'])
            ->with('identifications', ['name' => 'card', 'code' => 'some-employee-card-code'])
            ->with('timeClockLogs', [
                'work_shift_id' => null,
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
    public function testToCheckInWhenEmployeeHasThreeWorkShiftsWithOutOverlappingAndArrivesToTheFirstOne(ApiTester $I)
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 06, 00));

        $employee = factory(User::class)
            ->with('roles', ['name' => 'employee'])
            ->with('identifications', ['name' => 'card', 'code' => 'some-employee-card-code'])
            ->create();

        $employee->workShifts()->createMany([
            ['name' => '6 to 2', 'start_time' => '06:00', 'end_time' => '14:00'],
            ['name' => '2 to 10', 'start_time' => '14:00', 'end_time' => '22:00'],
            ['name' => '10 to 6', 'start_time' => '22:00', 'end_time' => '06:00'],
        ]);

        $requestData = [
            'action' => 'check_in',
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(201);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('time_clock_logs', [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->first()->id, // 6 to 2
            'checked_in_at' => now()->toDateTimeString(),
            'checked_in_by_id' => $this->user->id,
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function testToCheckInWhenEmployeeHasThreeWorkShiftsWithOutOverlappingAndArrivesToTheLastOne(ApiTester $I)
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 22, 00));

        $employee = factory(User::class)
            ->with('roles', ['name' => 'employee'])
            ->with('identifications', ['name' => 'card', 'code' => 'some-employee-card-code'])
            ->create();

        $employee->workShifts()->createMany([
            ['name' => '6 to 2', 'start_time' => '06:00', 'end_time' => '14:00'],
            ['name' => '2 to 10', 'start_time' => '14:00', 'end_time' => '22:00'],
            ['name' => '10 to 6', 'start_time' => '22:00', 'end_time' => '06:00'],
        ]);

        $requestData = [
            'action' => 'check_in',
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(201);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('time_clock_logs', [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->last()->id, // 6 to 2
            'checked_in_at' => now()->toDateTimeString(),
            'checked_in_by_id' => $this->user->id,
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function testToCheckOutWhenEmployeeHasNotCheckIn(ApiTester $I)
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 18, 00));
        $checkedInTime = now()->setTime(7, 0);

        $employee = factory(User::class)
            ->with('roles', ['name' => 'employee'])
            ->with('identifications', ['name' => 'card', 'code' => 'some-employee-card-code'])
            ->create();

        $requestData = [
            'action' => 'check_out',
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(422);
        $I->seeResponseJsonMatchesJsonPath('$.message');
    }
}
