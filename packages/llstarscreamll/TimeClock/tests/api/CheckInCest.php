<?php

namespace ClockTime;

use Illuminate\Support\Carbon;
use TimeClockPermissionsSeeder;
use Illuminate\Support\Facades\Artisan;
use llstarscreamll\Employees\Models\Employee;
use llstarscreamll\Company\Models\SubCostCenter;
use llstarscreamll\Novelties\Models\NoveltyType;
use llstarscreamll\TimeClock\Events\CheckedInEvent;
use llstarscreamll\Novelties\Enums\NoveltyTypeOperator;

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
     * @var \llstarscreamll\Company\Models\SubCostCenter
     */
    private $subCostCenter;

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        $I->disableMiddleware();

        Artisan::call('db:seed', ['--class' => TimeClockPermissionsSeeder::class]);
        $this->user = $I->amLoggedAsAdminUser();
        $this->subCostCenter = factory(SubCostCenter::class)->create();

        // novelty types
        factory(NoveltyType::class, 2)->create([
            'operator' => NoveltyTypeOperator::Subtraction,
            'context_type' => 'elegible_by_user',
        ]);

        factory(NoveltyType::class)->create([
            'operator' => NoveltyTypeOperator::Addition,
            'context_type' => 'elegible_by_user',
        ]);

        factory(NoveltyType::class)->create([
            'operator' => NoveltyTypeOperator::Subtraction, 'code' => 'PP',
        ]);

        $I->haveHttpHeader('Accept', 'application/json');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenHasSingleWorkShiftAndArrivesOnTime(ApiTester $I)
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 07, 00));

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 18',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']],
            ])
            ->create();

        $requestData = [
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(201);
        $I->seeEventTriggered(CheckedInEvent::class);
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
    public function whenHasSpecifiedWorkShiftIdAndArrivesOnTime(ApiTester $I)
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 07, 00));

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            // work shifts with same start time
            ->with('workShifts', [
                'name' => '7 to 18',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']],
            ])
            ->andWith('workShifts', [
                'name' => '7 to 15',
                'applies_on_days' => [1], // monday
                'time_slots' => [['start' => '07:00', 'end' => '15:00']],
            ])
            ->create();

        $requestData = [
            'work_shift_id' => $employee->workShifts->first()->id, // specify work shift id
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(201);
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
    public function whenHasWorkShiftsWithOverlapInTimeOnly(ApiTester $I)
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 07, 00));

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            // work shifts with same start time
            ->with('workShifts', [
                'name' => '7 to 18',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']],
            ])
            ->andWith('workShifts', [
                'name' => '7 to 15',
                'applies_on_days' => [6], // saturday
                'time_slots' => [['start' => '07:00', 'end' => '15:00']],
            ])
            ->create();

        $requestData = [
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);
        $I->seeResponseCodeIs(201);
        $I->seeRecord('time_clock_logs', [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->first()->id,
            'checked_in_at' => now()->toDateTimeString(),
            'checked_in_by_id' => $this->user->id,
        ]);
    }

    /**
     * @todo validate if this is a correct case, should return 201 or 422?
     * @test
     * @param ApiTester $I
     */
    public function whenHasNotWorkShift(ApiTester $I)
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 07, 00));

        // employee without work shift
        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->create();

        $requestData = [
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
     * @todo validate if this is a correct case, should return 201 or 422?
     * @test
     * @param ApiTester $I
     */
    public function whenHasWorkShiftButIsNotOnStartTimeRange(ApiTester $I)
    {
        // employee without work shift
        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 18',
                'applies_on_days' => [1, 2, 3, 4, 5], // *monday* to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']], // should check in at 7am
            ])
            ->create();

        // fake current date time, its *monday* out of time range, but the work shift
        // should be deducted by the current day
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 06, 00));

        $requestData = [
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['code' => 1054]); // work shift deducted, but a novelty type must be specified
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.code');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.title');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.detail');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.action');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.employee');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.punctuality');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.work_shifts');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.sub_cost_centers');
        // should return posible work shifts
        $I->seeResponseContainsJson(['work_shifts' => ['id' => $employee->workShifts->first()->id]]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenHasAlreadyCheckedIn(ApiTester $I)
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 07, 02));

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('timeClockLogs', [
                'checked_in_at' => Carbon::now()->subMinutes(2), // employee checked in 2 minutes ago
                'checked_out_at' => null,
                'checked_in_by_id' => $this->user->id,
            ])
            ->create();

        $requestData = [
            'identification_code' => 'fake-employee-card-code',
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['code' => 1050]);
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.code');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.title');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.detail');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenIdentificationCodeDoesNotExists(ApiTester $I)
    {
        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->create();

        $requestData = [
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
    public function whenHasShiftsWithOverlapOnTimeAndDays(ApiTester $I)
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 07, 00));

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            // work shifts with same start time
            ->with('workShifts', [
                'name' => '7 to 18',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']],
            ])
            ->andWith('workShifts', [
                'name' => '7 to 15',
                'applies_on_days' => [1], // monday
                'time_slots' => [['start' => '07:00', 'end' => '15:00']],
            ])
            ->create();

        $requestData = [
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['code' => 1051]);
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.code');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.title');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.detail');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.action');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.employee');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.punctuality');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.work_shifts');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.sub_cost_centers');
        $I->seeResponseContainsJson(['novelty_types' => [['id' => 1], ['id' => 2], ['id' => 3]]]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenHasWorkShiftButChecksAfterMaxTimeSlotAndWantsToIgnoreWorkShiftData(ApiTester $I)
    {
        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 18',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']], // should check in at 7am
            ])
            ->create();

        // fake current date time, check in after max end time slot
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 19, 00));

        // novelty sub cost center is send on `sub_cost_center_id` field
        $requestData = [
            'work_shift_id' => -1, // without specific work shift
            'novelty_type_id' => 3, // addition novelty type registered must be provided
            'sub_cost_center_id' => $this->subCostCenter->id, // sub cost center id because of novelty type
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(201);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('time_clock_logs', [
            'employee_id' => $employee->id,
            'work_shift_id' => null, // empty work shift
            'check_in_novelty_type_id' => 3,
            'check_in_sub_cost_center_id' => $this->subCostCenter->id,
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenHasSingleWorkShiftAndArrivesTooLate(ApiTester $I)
    {
        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 18',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']], // should check in at 7am
            ])
            ->create();

        // fake current date time, one hour late
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 8, 00));

        $requestData = [
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['code' => 1053]);
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.code');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.title');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.detail');
        // should return novelties that subtract time
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types.0.id');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types.1.id');
        $I->dontSeeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types.2.id');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.code');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.title');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.detail');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.action');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.employee');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.punctuality');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.work_shifts');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.sub_cost_centers');
        // novelty types
        $I->seeResponseContainsJson(['novelty_types' => ['id' => 1]]);
        $I->seeResponseContainsJson(['novelty_types' => ['id' => 2]]);
        $I->dontSeeResponseContainsJson(['novelty_types' => ['id' => 3]]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenHasSingleWorkShiftAndArrivesTooLateButNoveltyIsNotRequired(ApiTester $I)
    {
        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 18',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']], // should check in at 7am
            ])->create();

        // fake current date time, one hour late
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 8, 00));

        // set setting to NOT require novelty type when check in is too late
        $I->callArtisan('db:seed', ['--class' => 'TimeClockSettingsSeeder']);

        $requestData = [
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(201);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('time_clock_logs', [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->first()->id,
            'check_in_novelty_type_id' => 4,
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenHasSingleWorkShiftAndArrivesTooLateWithRightNoveltyType(ApiTester $I)
    {
        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 18',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']], // should check in at 7am
            ])
            ->create();

        // fake current date time, one hour late
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 8, 00));

        $requestData = [
            'novelty_type_id' => 1, // subtraction novelty type
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(201);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('time_clock_logs', [
            'employee_id' => $employee->id,
            'check_in_novelty_type_id' => 1, // subtraction novelty type
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenHasSingleWorkShiftAndArrivesTooLateWithWrongNoveltyType(ApiTester $I)
    {
        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 18',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']], // should check in at 7am
            ])
            ->create();

        // fake current date time, one hour late
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 8, 00));

        $requestData = [
            'novelty_type_id' => 3, // wrong addition novelty type
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['code' => 1055]);
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.code');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.title');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.detail');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.action');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.employee');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.punctuality');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.work_shifts');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.sub_cost_centers');
        // should return subtract novelty types
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types.0.id');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types.1.id');
        $I->dontSeeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types.2.id');
        $I->seeResponseContainsJson(['novelty_types' => ['id' => 1]]);
        $I->seeResponseContainsJson(['novelty_types' => ['id' => 2]]);
        $I->dontSeeResponseContainsJson(['novelty_types' => ['id' => 3]]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenHasSingleWorkShiftAndArrivesAfterMaxEndTimeSlot(ApiTester $I)
    {
        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 18', // max is 18:00
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']], // should check in at 7am
            ])
            ->create();

        // fake current date time, after max time slot hour late
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 19, 00)); // 19:00

        $requestData = [
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['code' => 1051]); // CanNotDeductWorkShiftException
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.code');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.title');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.detail');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.code');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.title');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.detail');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.action');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.employee');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.punctuality');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.work_shifts');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.sub_cost_centers');
        // without work shifts
        $I->dontSeeResponseJsonMatchesJsonPath('$.errors.0.meta.work_shifts.0');
        // addition novelty types
        $I->seeResponseContainsJson(['novelty_types' => ['id' => 3]]);
        $I->dontSeeResponseContainsJson(['novelty_types' => ['id' => 1]]);
        $I->dontSeeResponseContainsJson(['novelty_types' => ['id' => 2]]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenHasSingleWorkShiftAndArrivesTooEarlyWithoutNoveltyTypeSpecified(ApiTester $I)
    {
        // fake current date time, one hour late
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 6, 00));

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 18',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']], // should check in at 7am
            ])
            ->create();

        $requestData = [
            'work_shift_id' => $employee->workShifts->first()->id, // specify work shift id
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['code' => 1054]);
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.code');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.title');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.detail');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.action');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.employee');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.punctuality');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.work_shifts');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.sub_cost_centers');
        // should return addition novelty types
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types.0.id');
        $I->dontSeeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types.1.id');
        $I->dontSeeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types.2.id');
        $I->dontSeeResponseContainsJson(['novelty_types' => ['id' => 1]]);
        $I->dontSeeResponseContainsJson(['novelty_types' => ['id' => 2]]);
        $I->seeResponseContainsJson(['novelty_types' => ['id' => 3]]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenHasSingleWorkShiftAndArrivesTooEarlyWithRightNoveltyTypeButWithoutSuBcostCenter(ApiTester $I)
    {
        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 18',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']], // should check in at 7am
            ])
            ->create();

        // fake current date time, one hour early
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 6, 00));

        $requestData = [
            'work_shift_id' => 1,
            'novelty_type_id' => 3, // addition novelty type
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['errors' => ['code' => 1056]]);
        $I->seeResponseJsonMatchesJsonPath('$.message');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.code');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.title');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.detail');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.action');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.employee');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.punctuality');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.work_shifts');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.sub_cost_centers');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenHasSingleWorkShiftAndArrivesTooEarlyWithRightNoveltyType(ApiTester $I)
    {
        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 18',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']], // should check in at 7am
            ])
            ->create();

        // fake current date time, one hour early
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 6, 00));

        $requestData = [
            'work_shift_id' => 1,
            'novelty_type_id' => 3, // addition novelty type
            'sub_cost_center_id' => $this->subCostCenter->id,
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(201);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('time_clock_logs', [
            'employee_id' => $employee->id,
            'work_shift_id' => 1,
            'check_in_novelty_type_id' => 3, // addition novelty type
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenHasSingleWorkShiftAndArrivesTooEarlyWithWrongNoveltyType(ApiTester $I)
    {
        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 18',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']], // should check in at 7am
            ])
            ->create();

        // fake current date time, one hour early
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 6, 00));

        $requestData = [
            // when checking is too early, there is no way to know the work shift,
            // so the work shift id should be provided
            'work_shift_id' => $employee->workShifts->first()->id,
            'novelty_type_id' => 1, // wrong subtraction novelty type
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['code' => 1055]); // InvalidNoveltyTypeException
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.code');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.title');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.detail');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.action');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.employee');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.punctuality');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.work_shifts');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.sub_cost_centers');
        // should return addition novelty types
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types.0.id');
        $I->dontSeeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types.1.id');
        $I->dontSeeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types.2.id');
        $I->dontSeeResponseContainsJson(['novelty_types' => ['id' => 1]]);
        $I->dontSeeResponseContainsJson(['novelty_types' => ['id' => 2]]);
        $I->seeResponseContainsJson(['novelty_types' => ['id' => 3]]);
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
