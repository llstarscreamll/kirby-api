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
    public function testWhenEmployeeHasSingleWorkShiftAndLeavesOnTime(ApiTester $I)
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
    public function testWhenEmployeeHasNotWorkShift(ApiTester $I)
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 18, 00));
        $checkedInTime = now()->setTime(7, 0);

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
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
    public function testWhenEmployeeHasNotCheckIn(ApiTester $I)
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 18, 00));
        $checkedInTime = now()->setTime(7, 0);

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->create();

        $requestData = [
            'action' => 'check_out',
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(422);
        $I->seeResponseJsonMatchesJsonPath('$.message');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function testWhenEmployeeHasThreeWorkShiftsWithOutOverlappingAndLeavesOnTheLastOne(ApiTester $I)
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 06, 00));

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('timeClockLogs', [
                'work_shift_id' => null,
                'checked_in_at' => now()->subDay()->setTime(22, 00),
                'checked_in_by_id' => $this->user->id,
            ])
            ->create();

        $employee->workShifts()->createMany([
            ['name' => '6 to 2', 'time_slots' => [['start' => '06:00', 'end' => '14:00']]],
            ['name' => '2 to 10', 'time_slots' => [['start' => '14:00', 'end' => '22:00']]],
            ['name' => '10 to 6', 'time_slots' => [['start' => '22:00', 'end' => '06:00']]],
        ]);

        $requestData = [
            'action' => 'check_out',
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('time_clock_logs', [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->last()->id, // 6 to 2
            'checked_in_at' => now()->subDay()->setTime(22, 00)->toDateTimeString(),
            'checked_in_by_id' => $this->user->id,
            'checked_out_at' => now()->toDateTimeString(),
            'checked_out_by_id' => $this->user->id,
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function testWhenEmployeeHasThreeWorkShiftsWithOutOverlappingAndLeavesToTheFirstOne(ApiTester $I)
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 14, 00));

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('timeClockLogs', [
                'work_shift_id' => null,
                'checked_in_at' => now()->setTime(06, 00),
                'checked_in_by_id' => $this->user->id,
            ])
            ->create();

        $employee->workShifts()->createMany([
            ['name' => '6 to 2', 'time_slots' => [['start' => '06:00', 'end' => '14:00']]],
            ['name' => '2 to 10', 'time_slots' => [['start' => '14:00', 'end' => '22:00']]],
            ['name' => '10 to 6', 'time_slots' => [['start' => '22:00', 'end' => '06:00']]],
        ]);

        $requestData = [
            'action' => 'check_out',
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('time_clock_logs', [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->first()->id, // 6 to 2
            'checked_in_at' => now()->setTime(06, 00)->toDateTimeString(),
            'checked_in_by_id' => $this->user->id,
            'checked_out_at' => now()->toDateTimeString(),
            'checked_out_by_id' => $this->user->id,
        ]);
    }
}
