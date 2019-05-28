<?php
namespace ClockTime;

use Illuminate\Support\Carbon;
use llstarscreamll\Employees\Models\Employee;

/**
 * Class CheckInCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CheckInCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/time-clock/check-in';

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
    public function whenIdentificationCodeDoesNotExistsThenReturnUnprocesableEntity(ApiTester $I)
    {
        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
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
    public function whenInvalidActionTypeGivenThenReturnUnprocesableEntity(ApiTester $I)
    {
        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->create();

        $requestData = [
            'action' => 'invalid_option_here',
            'identification_code' => 'fake-employee-card-code',
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
    public function whenEmployeeHasAlreadyCheckedInThenReturnUnprocesableEntity(ApiTester $I)
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 07, 02));

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('timeClockLogs', [
                'checked_in_at' => Carbon::now()->subMinutes(2), // employee checked in 2 minutes ago
                'checked_in_by_id' => $this->user->id,
            ])
            ->create();

        $requestData = [
            'action' => 'check_in',
            'identification_code' => 'fake-employee-card-code',
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(422);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenEmployeeHasSingleWorkShiftAndArrivesOnTimeThenReturnCreated(ApiTester $I)
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 07, 00));

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', ['name' => '7 to 18', 'time_slots' => [['start' => '07:00', 'end' => '18:00']]])
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
    public function whenEmployeeHasShiftsOverlappingAndArrivesOnTimeThenReturnUnprocesableEntity(ApiTester $I)
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 07, 00));

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            // work shifts with same start time
            ->with('workShifts', ['name' => '7 to 18', 'time_slots' => [['start' => '07:00', 'end' => '18:00']]])
            ->andWith('workShifts', ['name' => '7 to 15', 'time_slots' => [['start' => '07:00', 'end' => '15:00']]])
            ->create();

        $requestData = [
            'action' => 'check_in',
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(422);
    }

    /**
     * @todo validate if this is a correct case, should return 201 or 422?
     * @test
     * @param ApiTester $I
     */
    public function whenEmployeeHasNotWorkShiftThenReturnCreated(ApiTester $I)
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 07, 00));

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
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
}
