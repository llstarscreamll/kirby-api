<?php

namespace ClockTime;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Kirby\Company\Models\SubCostCenter;
use Kirby\Employees\Models\Employee;
use Kirby\Novelties\Enums\DayType;
use Kirby\Novelties\Enums\NoveltyTypeOperator;
use Kirby\Novelties\Models\Novelty;
use Kirby\Novelties\Models\NoveltyType;
use Kirby\TimeClock\Events\CheckedInEvent;
use Kirby\TimeClock\Models\Setting;
use Kirby\TimeClock\Models\TimeClockLog;
use Kirby\WorkShifts\Models\WorkShift;
use TimeClockPermissionsSeeder;

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
     * @var \Kirby\Users\Models\User
     */
    private $user;

    /**
     * @var \Kirby\Company\Models\SubCostCenter
     */
    private $subCostCenter;

    /**
     * @var \Kirby\Novelties\Models\NoveltyType
     */
    private $subtractTimeNovelty;

    /**
     * @var \Kirby\Novelties\Models\NoveltyType
     */
    private $additionalTimeNovelty;

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
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

        $this->subtractTimeNovelty = factory(NoveltyType::class)->create([
            'operator' => NoveltyTypeOperator::Subtraction, 'code' => 'PP',
        ]);

        $this->additionalTimeNovelty = factory(NoveltyType::class)->create([
            'operator' => NoveltyTypeOperator::Addition, 'code' => 'HADI',
        ]);

        $I->haveHttpHeader('Accept', 'application/json');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenArrivesTooEarlyShouldReturnCorrectNoveltyTypes(ApiTester $I)
    {
        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 18',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']], // should check in at 7am
            ])
            ->create();

        NoveltyType::whereNotNull('id')->delete();

        // daytime overtime
        $expectedNoveltyType = factory(NoveltyType::class)->create([
            'operator' => NoveltyTypeOperator::Addition,
            'apply_on_days_of_type' => DayType::Workday,
            'apply_on_time_slots' => [
                ['start' => '06:00:00', 'end' => '21:00:00'],
            ],
            'context_type' => 'elegible_by_user',
        ]);

        // nighttime overtime
        factory(NoveltyType::class)->create([
            'operator' => NoveltyTypeOperator::Addition,
            'apply_on_days_of_type' => DayType::Workday,
            'apply_on_time_slots' => [
                ['start' => '21:00:00', 'end' => '06:00:00'],
            ],
            'context_type' => 'elegible_by_user',
        ]);

        // festive daytime overtime
        factory(NoveltyType::class)->create([
            'operator' => NoveltyTypeOperator::Addition,
            'apply_on_days_of_type' => DayType::Holiday,
            'apply_on_time_slots' => [
                ['start' => '06:00:00', 'end' => '21:00:00'],
            ],
            'context_type' => 'elegible_by_user',
        ]);

        // fake current date time, one hour late
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 6, 00)); // monday

        $requestData = [
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        // only the expected novelty type should be returned
        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['code' => 1054]);
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types.0');
        $I->dontSeeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types.1');
        $I->seeResponseContainsJson(['novelty_types' => ['id' => $expectedNoveltyType->id]]);
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
            'expected_check_in_at' => now()->setTime(07, 00)->toDateTimeString(),
            'checked_in_at' => now()->toDateTimeString(),
            'checked_in_by_id' => $this->user->id,
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenHasThreeConcatenatedWorkShiftsEachOneWithEightHours(ApiTester $I)
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 22, 00));

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '22 to 06',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '22:00', 'end' => '06:00']], // night work shift
            ])
            ->create();

        $employee->workShifts()->attach(factory(WorkShift::class)->create([
            'name' => '14 to 22',
            'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
            'time_slots' => [['start' => '14:00', 'end' => '22:00']], // night work shift
        ]));

        $employee->workShifts()->attach(factory(WorkShift::class)->create([
            'name' => '06 to 14',
            'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
            'time_slots' => [['start' => '06:00', 'end' => '14:00']], // night work shift
        ]));

        $requestData = [
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(201);
        $I->seeEventTriggered(CheckedInEvent::class);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('time_clock_logs', [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->where('name', '22 to 06')->first()->id,
            'expected_check_in_at' => now()->setTime(22, 00)->toDateTimeString(),
            'checked_in_at' => now()->toDateTimeString(),
            'checked_in_by_id' => $this->user->id,
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenHasSingleWorkShiftWithTwoTimeSlotsAndLogInFirstSlotAndArrivesOnTimeToTheSecondSlot(ApiTester $I)
    {
        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 18',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [
                    ['start' => '07:00', 'end' => '12:00'],
                    ['start' => '13:30', 'end' => '18:00'], // should check in from 13:20 to 13:40
                ],
            ])
            ->create();

        // fake current date time, monday, on time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 13, 35));

        // create another check in in another time
        $previousTimeClockLog = factory(TimeClockLog::class)->create([
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->first()->id,
            'checked_in_at' => now()->setTime(07, 07),
            'check_in_sub_cost_center_id' => $this->subCostCenter->id,
            'checked_out_at' => now()->setTime(12, 02),
        ]);

        factory(Novelty::class)->create([
            'time_clock_log_id' => $previousTimeClockLog->id,
            'novelty_type_id' => $this->additionalTimeNovelty->first()->id,
            'employee_id' => $employee->id,
            'start_at' => now()->setTime(07, 07),
            'end_at' => now()->setTime(12, 02),
        ]);

        $requestData = [
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);
        $I->seeResponseCodeIs(201);
        $I->seeRecord('time_clock_logs', [
            'employee_id' => $employee->id,
            'check_in_sub_cost_center_id' => null,
            'expected_check_in_at' => now()->setTime(13, 30)->toDateTimeString(),
            'checked_in_at' => now()->toDateTimeString(),
            'work_shift_id' => $employee->workShifts->first()->id,
            'check_in_novelty_type_id' => null, // with empty novelty type on check in because employee is on time
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenHasSingleWorkShiftWithTwoTimeSlotsAndLogInFirstSlotAndArrivesTooLateToTheSecondSlot(ApiTester $I)
    {
        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 18',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [
                    ['start' => '07:00', 'end' => '12:00'],
                    ['start' => '13:30', 'end' => '18:00'], // should check in from 13:20 to 13:40
                ],
            ])
            ->create();

        // fake current date time, monday, too late
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 14, 00));

        // create another check in in another time
        $previousTimeClockLog = factory(TimeClockLog::class)->create([
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->first()->id,
            'checked_in_at' => now()->setTime(07, 07),
            'check_in_sub_cost_center_id' => $this->subCostCenter->id,
            'checked_out_at' => now()->setTime(12, 02),
        ]);

        factory(Novelty::class)->create([
            'time_clock_log_id' => $previousTimeClockLog->id,
            'novelty_type_id' => $this->additionalTimeNovelty->first()->id,
            'employee_id' => $employee->id,
            'start_at' => now()->setTime(07, 07),
            'end_at' => now()->setTime(12, 02),
        ]);

        $requestData = [
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        // work shift deducted, but is too late, then a novelty type must be specified
        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['code' => 1053]);
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
            'expected_check_in_at' => now()->setTime(07, 00)->toDateTimeString(),
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
            'expected_check_in_at' => now()->setTime(07, 00)->toDateTimeString(),
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
            'expected_check_in_at' => null,
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
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 07, 00)); // monday, workday

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

        // work shift cant be deducted since have collisions in day and start time
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
            'expected_check_in_at' => null,
            'checked_in_at' => now()->toDateTimeString(),
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
    public function whenHasSingleWorkShiftAndArrivesTooLateClosedToWorkShiftEndandspecifyworkShiftIdAndStartNoveltyIsNotRequired(ApiTester $I)
    {
        // set setting to NOT require novelty type when check in is too late,
        // this make to set a default novelty type id for the late check in
        $I->callArtisan('db:seed', ['--class' => 'TimeClockSettingsSeeder']);

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 18',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']], // should check in at 7am
            ])
            ->create();

        // fake current date time, one hour late
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 16, 00));

        $requestData = [
            'identification_code' => $employee->identifications->first()->code,
            'work_shift_id' => $employee->workShifts->first()->id,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(201);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('time_clock_logs', [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->first()->id,
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
            'expected_check_in_at' => now()->setTime(07, 00)->toDateTimeString(),
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

    // ######################################################################### #
    //                         Scheduled novelties tests                        #
    // ######################################################################### #

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldNotSetDefaultNoveltyWhenArrivesOnTimeForScheduledNovelty(ApiTester $I)
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

        // set setting to NOT require novelty type when check in is too late,
        // this make to set a default novelty type id for the late check in
        $I->callArtisan('db:seed', ['--class' => 'TimeClockSettingsSeeder']);

        // create scheduled novelty, this will make not to set the default
        // novelty for the late check in
        $noveltyData = [
            'employee_id' => $employee->id,
            'start_at' => now()->subHour(),
            'end_at' => now(),
        ];

        factory(Novelty::class)->create($noveltyData);

        $requestData = [
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $expectedTimeClockLog = [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->first()->id,
            'check_in_novelty_type_id' => null,
        ];

        $I->seeResponseCodeIs(201);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('time_clock_logs', $expectedTimeClockLog);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldSetDefaultNoveltyWhenArrivesTooLateForScheduledNovelty(ApiTester $I)
    {
        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 18',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']], // should check in at 7am
            ])->create();

        // fake current date time, monday at 9am, two hours late
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 9, 00));

        // set setting to NOT require novelty type when check in is too late,
        // this make to set a default novelty type id for the late check in
        $I->callArtisan('db:seed', ['--class' => 'TimeClockSettingsSeeder']);

        // create scheduled novelty from 7am to 8am, since employee arrives at
        // 9am, he's 1 hour late to check in, so the default novelty type for
        // check in should be setted
        $noveltyData = [
            'employee_id' => $employee->id,
            'start_at' => now()->subHours(2),
            'end_at' => now()->subHour(),
        ];

        factory(Novelty::class)->create($noveltyData);

        $requestData = [
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $expectedTimeClockLog = [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->first()->id,
            'check_in_novelty_type_id' => $this->subtractTimeNovelty->id,
        ];

        $I->seeResponseCodeIs(201);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('time_clock_logs', $expectedTimeClockLog);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldUpdateScheduledNoveltyEndDateWhenArrivesTooLateToSaidNoveltyEnd(ApiTester $I)
    {
        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 18',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']], // should check in at 7am
            ])->create();

        // fake current date time, monday at 9am, two hours late
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 9, 00));

        // set setting to NOT require novelty type when check in is too late,
        // this make to set a default novelty type id for the late check in
        $I->callArtisan('db:seed', ['--class' => 'TimeClockSettingsSeeder']);
        Setting::where(['key' => 'time-clock.adjust-scheduled-novelties-times-based-on-checks'])->update(['value' => true]);

        // create scheduled novelty from 7am to 8am, since employee arrives at
        // 9am, he's 1 hour late to check in, so the default novelty type for
        // check in should NOT be setted and novelty end_at attribute
        // should be adjusted to the employee entry time (9am)
        $noveltyData = [
            'employee_id' => $employee->id,
            'start_at' => now()->setTime(7, 0), // 7am
            'end_at' => now()->setTime(8, 0), // 8am
        ];

        $scheduledNovelty = factory(Novelty::class)->create($noveltyData);

        $requestData = [
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $expectedTimeClockLog = [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->first()->id,
            'check_in_novelty_type_id' => null,
        ];

        $I->seeResponseCodeIs(201);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('time_clock_logs', $expectedTimeClockLog);
        $I->seeRecord('novelties', [
            'id' => $scheduledNovelty->id,
            'start_at' => now()->setTime(7, 0),
            'end_at' => now()->setTime(9, 0),
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldNotUpdateScheduledNoveltyEndDateWhenArrivesTooLateToWorkShift(ApiTester $I)
    {
        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 18',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']], // should check in at 7am
            ])->create();

        // fake current date time, monday at 8am, two hours late
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 8, 00));

        // set setting to NOT require novelty type when check in is too late,
        // this make to set a default novelty type id for the late check in
        $I->callArtisan('db:seed', ['--class' => 'TimeClockSettingsSeeder']);
        Setting::where(['key' => 'time-clock.adjust-scheduled-novelties-times-based-on-checks'])->update(['value' => true]);

        // create scheduled novelty from 10am to 11am, since employee arrives at
        // 8am (too late), he's 1 hour late to check in, so the default novelty
        // type for check in should be setted and the scheduled novelty attr
        // 'end_at' should NOT be adjusted since this es not the
        // time to burn that scheduled novelty
        $noveltyData = [
            'employee_id' => $employee->id,
            'start_at' => now()->setTime(10, 0), // 10am
            'end_at' => now()->setTime(11, 0), // 11am
        ];

        $scheduledNovelty = factory(Novelty::class)->create($noveltyData);

        $requestData = [
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $expectedTimeClockLog = [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->first()->id,
            'check_in_novelty_type_id' => $this->subtractTimeNovelty->id,
        ];

        $I->seeResponseCodeIs(201);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('time_clock_logs', $expectedTimeClockLog);
        // scheduled novelty should not be updated
        $I->seeRecord('novelties', [
            'id' => $scheduledNovelty->id,
            'start_at' => now()->setTime(10, 0)->toDateTimeString(), // 10am
            'end_at' => now()->setTime(11, 0)->toDateTimeString(), // 11am
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldIgnoreCheckoutScheduledNovelties(ApiTester $I)
    {
        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 18',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']], // should check in at 7am
            ])->create();

        // fake current date time, monday at 7am, on time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 7, 00));

        // set setting to NOT require novelty type when check in is too late,
        // this make to set a default novelty type id for the late check in
        $I->callArtisan('db:seed', ['--class' => 'TimeClockSettingsSeeder']);

        // create scheduled novelty from 5pm to 6pm, since employee arrives at
        // 7am, he's on time to check in, said novelty takes no effect for this
        // check in action, because the novelty time is not in the check in time
        // range
        $noveltyData = [
            'employee_id' => $employee->id,
            'start_at' => now()->setTime(17, 00), // 5pm
            'end_at' => now()->setTime(18, 00), // 6pm
        ];

        factory(Novelty::class)->create($noveltyData);

        $requestData = [
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $expectedTimeClockLog = [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->first()->id,
            'check_in_novelty_type_id' => null,
        ];

        $I->seeResponseCodeIs(201);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('time_clock_logs', $expectedTimeClockLog);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldBewareOfScheduledNoveltyOnTheMiddleOfWorkShift(ApiTester $I)
    {
        // fake current date time, monday at 12m, on time because of scheduled novelty
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 12, 00));

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 18',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']], // should check in at 7am
            ])
            ->with('timeClockLogs', [
                'work_shift_id' => 1,
                'checked_in_at' => now()->setTime(07, 00),
                'checked_out_at' => now()->setTime(10, 00),
                'checked_in_by_id' => $this->user->id,
            ])->create();

        // set setting to NOT require novelty type when check in is too late,
        // this make to set a default novelty type id for the late check in
        $I->callArtisan('db:seed', ['--class' => 'TimeClockSettingsSeeder']);

        // create scheduled novelty from 10am to 12m, employee has checked in at
        // 7am and checked out at 10am because the scheduled novelty from 10am to
        // 12m, then the employee arrives at 12am, he's on time to check in to
        // finish the remaining work shift time
        $noveltyData = [
            'employee_id' => $employee->id,
            'time_clock_log_id' => 1,
            'start_at' => now()->setTime(10, 00), // 10pm
            'end_at' => now()->setTime(12, 00), // 12m
        ];

        factory(Novelty::class)->create($noveltyData);

        $requestData = [
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $expectedTimeClockLog = [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->first()->id,
            'checked_in_at' => now()->toDateTimeString(),
            'check_in_novelty_type_id' => null,
        ];

        $I->seeResponseCodeIs(201);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('time_clock_logs', $expectedTimeClockLog);
    }

    // ######################################################################## #
    //            Automatic novelty deduction on eager/late check in           #
    // ######################################################################## #

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
            'check_in_novelty_type_id' => $this->subtractTimeNovelty->id,
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenHasSingleWorkShiftAndArrivesTooEarlyButNoveltyIsNotRequired(ApiTester $I)
    {
        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 18',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']], // should check in at 7am
            ])->create();

        // fake current date time, monday, one hour early
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 6, 00));

        // set setting to NOT require novelty type when check in is too early
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
            'check_in_novelty_type_id' => $this->additionalTimeNovelty->id,
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenEmployeeHasTwoWorkShiftsAndNoveltyIsNotRequiredShouldNotReturnNoveltyTypes(ApiTester $I)
    {
        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 17',
                'grace_minutes_before_start_times' => 25,
                'grace_minutes_after_end_times' => 20,
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [
                    ['start' => '07:00', 'end' => '12:00'], // should check in at 7am
                    ['start' => '13:30', 'end' => '17:00'],
                ],
            ])
            ->create();

        // create another work shift option for the employee
        $novelty = factory(WorkShift::class)->create([
            'name' => '7 to 18',
            'grace_minutes_before_start_times' => 25,
            'grace_minutes_after_end_times' => 20,
            'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
            'time_slots' => [
                ['start' => '07:00', 'end' => '12:00'], // should check in at 7am
                ['start' => '13:30', 'end' => '18:00'],
            ], ]);

        $employee->workShifts()->attach($novelty);

        // fake current date time, one hour late
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 14, 57));

        // set setting to NOT require novelty type when check in is too late
        $I->callArtisan('db:seed', ['--class' => 'TimeClockSettingsSeeder']);

        $requestData = [
            'identification_code' => $employee->identifications->first()->code,

        ];
        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(422);
        $I->dontSeeResponseJsonMatchesJsonPath('errors.0.meta.novelty_types.0');
        $I->dontSeeResponseJsonMatchesJsonPath('errors.0.meta.novelty_types.1');
    }

    // ######################################################################## #
    //                            Permissions tests                            #
    // ######################################################################## #

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
