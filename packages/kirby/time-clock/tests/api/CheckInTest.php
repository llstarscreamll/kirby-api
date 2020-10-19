<?php

namespace Kirby\TimeClock\Tests\api;

use DefaultNoveltyTypesSeed;
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
use TimeClockSettingsSeeder;

/**
 * Class CheckInTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CheckInTest extends \Tests\TestCase
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

    public function setUp(): void
    {
        parent::setUp();
        Artisan::call('db:seed', ['--class' => TimeClockPermissionsSeeder::class]);
        $this->actingAsAdmin($this->user = factory(\Kirby\Users\Models\User::class)->create());
        $this->subCostCenter = factory(SubCostCenter::class)->create();

        // novelty types
        factory(NoveltyType::class, 2)->create([
            'operator' => NoveltyTypeOperator::Subtraction,
            'apply_on_days_of_type' => null,
            'context_type' => 'elegible_by_user',
        ]);

        factory(NoveltyType::class)->create([
            'operator' => NoveltyTypeOperator::Addition,
            'apply_on_days_of_type' => null,
            'context_type' => 'elegible_by_user',
        ]);

        $this->subtractTimeNovelty = factory(NoveltyType::class)->create([
            'operator' => NoveltyTypeOperator::Subtraction, 'code' => 'PP',
            'apply_on_days_of_type' => null,
        ]);

        $this->additionalTimeNovelty = factory(NoveltyType::class)->create([
            'operator' => NoveltyTypeOperator::Addition, 'code' => 'HADI',
            'apply_on_days_of_type' => null,
        ]);
    }

    /**
     * @test
     */
    public function whenArrivesTooEarlyShouldReturnCorrectNoveltyTypes()
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

        $this->json('POST', $this->endpoint, $requestData)
            ->assertStatus(422)
            ->assertJsonFragment(['code' => 1054])
        // only the expected novelty type should be returned
            ->assertJsonHasPath('errors.0.meta.novelty_types.0')
            ->assertJsonMissingPath('errors.0.meta.novelty_types.1')
            ->assertJsonPath('errors.0.meta.novelty_types.0.id', $expectedNoveltyType->id);
    }

    /**
     * @test
     */
    public function whenHasSingleWorkShiftAndArrivesOnTime()
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

        $this->expectsEvents(CheckedInEvent::class);

        $this->json('POST', $this->endpoint, $requestData)
            ->assertCreated()
            ->assertJsonHasPath('data.id');

        $this->assertDatabaseHas('time_clock_logs', [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->first()->id,
            'expected_check_in_at' => now()->setTime(07, 00)->toDateTimeString(),
            'checked_in_at' => now()->toDateTimeString(),
            'checked_in_by_id' => $this->user->id,
        ]);
    }

    /**
     * @test
     */
    public function whenHasThreeConcatenatedWorkShiftsEachOneWithEightHours()
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

        $this->expectsEvents(CheckedInEvent::class);

        $this->json('POST', $this->endpoint, $requestData)
            ->assertCreated()
            ->assertJsonHasPath('data.id');

        $this->assertDatabaseHas('time_clock_logs', [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->where('name', '22 to 06')->first()->id,
            'expected_check_in_at' => now()->setTime(22, 00)->toDateTimeString(),
            'checked_in_at' => now()->toDateTimeString(),
            'checked_in_by_id' => $this->user->id,
        ]);
    }

    /**
     * @test
     */
    public function whenHasSingleWorkShiftWithTwoTimeSlotsAndLogInFirstSlotAndArrivesOnTimeToTheSecondSlot()
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

        $this->json('POST', $this->endpoint, $requestData)
            ->assertCreated();

        $this->assertDatabaseHas('time_clock_logs', [
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
     */
    public function whenHasSingleWorkShiftWithTwoTimeSlotsAndLogInFirstSlotAndArrivesTooLateToTheSecondSlot()
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

        $this->json('POST', $this->endpoint, $requestData)
        // work shift deducted, but is too late, then a novelty type must be specified
            ->assertStatus(422)
            ->assertJsonFragment(['code' => 1053]);
    }

    /**
     * @test
     */
    public function whenHasSpecifiedWorkShiftIdAndArrivesOnTime()
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

        $this->json('POST', $this->endpoint, $requestData)
            ->assertCreated();

        $this->assertDatabaseHas('time_clock_logs', [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->first()->id,
            'checked_in_at' => now()->toDateTimeString(),
            'expected_check_in_at' => now()->setTime(07, 00)->toDateTimeString(),
            'checked_in_by_id' => $this->user->id,
        ]);
    }

    /**
     * @test
     */
    public function whenHasWorkShiftsWithOverlapInTimeOnly()
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

        $this->json('POST', $this->endpoint, $requestData)
            ->assertCreated();

        $this->assertDatabaseHas('time_clock_logs', [
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
     */
    public function whenHasNotWorkShift()
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

        $this->json('POST', $this->endpoint, $requestData)
            ->assertCreated()
            ->assertJsonHasPath('data.id');

        $this->assertDatabaseHas('time_clock_logs', [
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
     */
    public function whenHasWorkShiftButIsNotOnStartTimeRange()
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

        $this->json('POST', $this->endpoint, $requestData)
            ->assertStatus(422)
            ->assertJsonFragment(['code' => 1054]) // work shift deducted, but a novelty type must be specified
            ->assertJsonHasPath('errors.0.code')
            ->assertJsonHasPath('errors.0.title')
            ->assertJsonHasPath('errors.0.detail')
            ->assertJsonHasPath('errors.0.meta')
            ->assertJsonHasPath('errors.0.meta.action')
            ->assertJsonHasPath('errors.0.meta.employee')
            ->assertJsonHasPath('errors.0.meta.punctuality')
            ->assertJsonHasPath('errors.0.meta.work_shifts')
            ->assertJsonHasPath('errors.0.meta.novelty_types')
            ->assertJsonHasPath('errors.0.meta.sub_cost_centers')
        // should return posible work shifts
            ->assertJsonPath('errors.0.meta.work_shifts.0.id', $employee->workShifts->first()->id);
    }

    /**
     * @test
     */
    public function whenHasAlreadyCheckedIn()
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 07, 02));

        factory(Employee::class)
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

        $this->json('POST', $this->endpoint, $requestData)
            ->assertStatus(422)
            ->assertJsonFragment(['code' => 1050])
            ->assertJsonHasPath('errors.0.code')
            ->assertJsonHasPath('errors.0.title')
            ->assertJsonHasPath('errors.0.detail');
    }

    /**
     * @test
     */
    public function whenIdentificationCodeDoesNotExists()
    {
        factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->create();

        $requestData = [
            'identification_code' => 'wrong_identification_here',
        ];

        $this->json('POST', $this->endpoint, $requestData)
            ->assertStatus(422)
            ->assertJsonHasPath('message')
            ->assertJsonHasPath('errors.identification_code');
    }

    /**
     * @test
     */
    public function whenHasShiftsWithOverlapOnTimeAndDays()
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

        $this->json('POST', $this->endpoint, $requestData)
            ->assertStatus(422) // work shift cant be deducted since have collisions in day and start time
            ->assertJsonFragment(['code' => 1051])
            ->assertJsonHasPath('errors.0.code')
            ->assertJsonHasPath('errors.0.title')
            ->assertJsonHasPath('errors.0.detail')
            ->assertJsonHasPath('errors.0.meta')
            ->assertJsonHasPath('errors.0.meta.action')
            ->assertJsonHasPath('errors.0.meta.employee')
            ->assertJsonHasPath('errors.0.meta.punctuality')
            ->assertJsonHasPath('errors.0.meta.work_shifts')
            ->assertJsonHasPath('errors.0.meta.novelty_types')
            ->assertJsonPath('errors.0.meta.novelty_types.0.id', 1)
            ->assertJsonPath('errors.0.meta.novelty_types.1.id', 2)
            ->assertJsonPath('errors.0.meta.novelty_types.2.id', 3)
            ->assertJsonHasPath('errors.0.meta.sub_cost_centers');
    }

    /**
     * @test
     */
    public function whenHasWorkShiftButChecksAfterMaxTimeSlotAndWantsToIgnoreWorkShiftData()
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

        $this->json('POST', $this->endpoint, $requestData)
            ->assertCreated()
            ->assertJsonHasPath('data.id');

        $this->assertDatabaseHas('time_clock_logs', [
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
     */
    public function whenHasSingleWorkShiftAndArrivesTooLate()
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

        $this->json('POST', $this->endpoint, $requestData)
            ->assertStatus(422)
            ->assertJsonFragment(['code' => 1053])
            ->assertJsonHasPath('errors.0.code')
            ->assertJsonHasPath('errors.0.title')
            ->assertJsonHasPath('errors.0.detail')
            ->assertJsonHasPath('errors.0.meta.novelty_types.0.id') // should return novelties that subtract time
            ->assertJsonHasPath('errors.0.meta.novelty_types.1.id')
            ->assertJsonPath('errors.0.meta.novelty_types.0.id', 1)
            ->assertJsonPath('errors.0.meta.novelty_types.1.id', 2)
            ->assertJsonMissingPath('errors.0.meta.novelty_types.2.id')
            ->assertJsonHasPath('errors.0.code')
            ->assertJsonHasPath('errors.0.title')
            ->assertJsonHasPath('errors.0.detail')
            ->assertJsonHasPath('errors.0.meta')
            ->assertJsonHasPath('errors.0.meta.action')
            ->assertJsonHasPath('errors.0.meta.employee')
            ->assertJsonHasPath('errors.0.meta.punctuality')
            ->assertJsonHasPath('errors.0.meta.work_shifts')
            ->assertJsonHasPath('errors.0.meta.novelty_types')
            ->assertJsonHasPath('errors.0.meta.sub_cost_centers');
    }

    /**
     * @test
     */
    public function whenHasSingleWorkShiftAndArrivesTooLateClosedToWorkShiftEndandspecifyworkShiftIdAndStartNoveltyIsNotRequired()
    {
        // set setting to NOT require novelty type when check in is too late,
        // this make to set a default novelty type id for the late check in
        $this->seed(TimeClockSettingsSeeder::class);

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

        $this->json('POST', $this->endpoint, $requestData)
            ->assertCreated()
            ->assertJsonHasPath('data.id');

        $this->assertDatabaseHas('time_clock_logs', [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->first()->id,
        ]);
    }

    /**
     * @test
     */
    public function whenHasSingleWorkShiftAndArrivesTooLateWithRightNoveltyType()
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

        $this->json('POST', $this->endpoint, $requestData)
            ->assertCreated()
            ->assertJsonHasPath('data.id');

        $this->assertDatabaseHas('time_clock_logs', [
            'employee_id' => $employee->id,
            'check_in_novelty_type_id' => 1, // subtraction novelty type
        ]);
    }

    /**
     * @test
     */
    public function whenHasSingleWorkShiftAndArrivesTooLateWithWrongNoveltyType()
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

        $this->json('POST', $this->endpoint, $requestData)
            ->assertStatus(422)
            ->assertJsonFragment(['code' => 1055])
            ->assertJsonHasPath('errors.0.code')
            ->assertJsonHasPath('errors.0.title')
            ->assertJsonHasPath('errors.0.detail')
            ->assertJsonHasPath('errors.0.meta')
            ->assertJsonHasPath('errors.0.meta.action')
            ->assertJsonHasPath('errors.0.meta.employee')
            ->assertJsonHasPath('errors.0.meta.punctuality')
            ->assertJsonHasPath('errors.0.meta.work_shifts')
            ->assertJsonHasPath('errors.0.meta.novelty_types')
            ->assertJsonHasPath('errors.0.meta.sub_cost_centers')
        // should return subtract novelty types
            ->assertJsonHasPath('errors.0.meta.novelty_types.0.id')
            ->assertJsonHasPath('errors.0.meta.novelty_types.1.id')
            ->assertJsonMissingPath('errors.0.meta.novelty_types.2.id')
            ->assertJsonPath('errors.0.meta.novelty_types.0.id', 1)
            ->assertJsonPath('errors.0.meta.novelty_types.1.id', 2);
    }

    /**
     * @test
     */
    public function whenHasSingleWorkShiftAndArrivesAfterMaxEndTimeSlot()
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

        $this->json('POST', $this->endpoint, $requestData)
            ->assertStatus(422)
            ->assertJsonFragment(['code' => 1051]) // CanNotDeductWorkShiftException
            ->assertJsonHasPath('errors.0.code')
            ->assertJsonHasPath('errors.0.title')
            ->assertJsonHasPath('errors.0.detail')
            ->assertJsonHasPath('errors.0.code')
            ->assertJsonHasPath('errors.0.title')
            ->assertJsonHasPath('errors.0.detail')
            ->assertJsonHasPath('errors.0.meta')
            ->assertJsonHasPath('errors.0.meta.action')
            ->assertJsonHasPath('errors.0.meta.employee')
            ->assertJsonHasPath('errors.0.meta.punctuality')
            ->assertJsonHasPath('errors.0.meta.work_shifts')
            ->assertJsonHasPath('errors.0.meta.novelty_types')
            ->assertJsonMissingPath('errors.0.meta.novelty_types.1')
            ->assertJsonMissingPath('errors.0.meta.novelty_types.2')
            ->assertJsonPath('errors.0.meta.novelty_types.0.id', 3)
            ->assertJsonHasPath('errors.0.meta.sub_cost_centers')
            ->assertJsonMissingPath('errors.0.meta.work_shifts.0'); // empty work shifts
    }

    /**
     * @test
     */
    public function whenHasSingleWorkShiftAndArrivesTooEarlyWithoutNoveltyTypeSpecified()
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

        $this->json('POST', $this->endpoint, $requestData)
            ->assertStatus(422)
            ->assertJsonFragment(['code' => 1054])
            ->assertJsonHasPath('errors.0.code')
            ->assertJsonHasPath('errors.0.title')
            ->assertJsonHasPath('errors.0.detail')
            ->assertJsonHasPath('errors.0.meta')
            ->assertJsonHasPath('errors.0.meta.action')
            ->assertJsonHasPath('errors.0.meta.employee')
            ->assertJsonHasPath('errors.0.meta.punctuality')
            ->assertJsonHasPath('errors.0.meta.work_shifts')
            ->assertJsonHasPath('errors.0.meta.novelty_types')
            ->assertJsonHasPath('errors.0.meta.sub_cost_centers')
            ->assertJsonHasPath('errors.0.meta.novelty_types.0.id') // should return addition novelty types
            ->assertJsonMissingPath('errors.0.meta.novelty_types.1.id')
            ->assertJsonMissingPath('errors.0.meta.novelty_types.2.id')
            ->assertJsonPath('errors.0.meta.novelty_types.0.id', 3);
    }

    /**
     * @test
     */
    public function whenHasSingleWorkShiftAndArrivesTooEarlyWithRightNoveltyTypeButWithoutSuBcostCenter()
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

        $this->json('POST', $this->endpoint, $requestData)
            ->assertStatus(422)
            ->assertJsonFragment(['code' => 1056])
            ->assertJsonHasPath('message')
            ->assertJsonHasPath('errors.0.code')
            ->assertJsonHasPath('errors.0.title')
            ->assertJsonHasPath('errors.0.detail')
            ->assertJsonHasPath('errors.0.meta')
            ->assertJsonHasPath('errors.0.meta.action')
            ->assertJsonHasPath('errors.0.meta.employee')
            ->assertJsonHasPath('errors.0.meta.punctuality')
            ->assertJsonHasPath('errors.0.meta.work_shifts')
            ->assertJsonHasPath('errors.0.meta.novelty_types')
            ->assertJsonHasPath('errors.0.meta.sub_cost_centers');
    }

    /**
     * @test
     */
    public function whenHasSingleWorkShiftAndArrivesTooEarlyWithRightNoveltyType()
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

        $this->json('POST', $this->endpoint, $requestData)
            ->assertCreated()
            ->assertJsonHasPath('data.id');
        $this->assertDatabaseHas('time_clock_logs', [
            'employee_id' => $employee->id,
            'work_shift_id' => 1,
            'expected_check_in_at' => now()->setTime(07, 00)->toDateTimeString(),
            'check_in_novelty_type_id' => 3, // addition novelty type
        ]);
    }

    /**
     * @test
     */
    public function whenHasSingleWorkShiftAndArrivesTooEarlyWithWrongNoveltyType()
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

        $this->json('POST', $this->endpoint, $requestData)
            ->assertStatus(422)
            ->assertJsonFragment(['code' => 1055]) // InvalidNoveltyTypeException
            ->assertJsonHasPath('errors.0.code')
            ->assertJsonHasPath('errors.0.title')
            ->assertJsonHasPath('errors.0.detail')
            ->assertJsonHasPath('errors.0.meta')
            ->assertJsonHasPath('errors.0.meta.action')
            ->assertJsonHasPath('errors.0.meta.employee')
            ->assertJsonHasPath('errors.0.meta.punctuality')
            ->assertJsonHasPath('errors.0.meta.work_shifts')
            ->assertJsonHasPath('errors.0.meta.novelty_types')
            ->assertJsonHasPath('errors.0.meta.sub_cost_centers')
            ->assertJsonHasPath('errors.0.meta.novelty_types.0.id') // should return addition novelty types
            ->assertJsonMissingPath('errors.0.meta.novelty_types.1.id')
            ->assertJsonMissingPath('errors.0.meta.novelty_types.2.id')
            ->assertJsonPath('errors.0.meta.novelty_types.0.id', 3);
    }

    // ######################################################################## #
    //                         Scheduled novelties tests                        #
    // ######################################################################## #

    /**
     * @test
     */
    public function shouldNotOverwriteLastNormalWorkShiftNoveltyWhenAdjustScheduledNolvetyTimeFlagIsOn()
    {
        $this->artisan('db:seed', ['--class' => DefaultNoveltyTypesSeed::class]);
        $noveltyTypes = NoveltyType::all();
        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '06-14',
                'grace_minutes_before_start_times' => 30,
                'grace_minutes_after_start_times' => 30,
                'grace_minutes_before_end_times' => 20,
                'grace_minutes_after_end_times' => 30,
                'meal_time_in_minutes' => 0,
                'min_minutes_required_to_discount_meal_time' => 0,
                'applies_on_days' => [1, 2, 3, 4, 5],
                'time_zone' => 'America/Bogota',
                'time_slots' => [['start' => '06:00', 'end' => '14:00']],
            ])
            ->create();

        $workShift = $employee->workShifts->first();

        $log = factory(TimeClockLog::class)->create([
            'employee_id' => $employee->id,
            'sub_cost_center_id' => factory(SubCostCenter::class)->create()->id,
            'work_shift_id' => $workShift->id,
            'checked_in_at' => now()->setDate(2020, 05, 20)->setTime(11, 27, 00),
            'checked_out_at' => now()->setDate(2020, 05, 20)->setTime(14, 49, 00),
            'check_out_novelty_type_id' => null,
            'check_out_sub_cost_center_id' => factory(SubCostCenter::class)->create()->id,
        ]);

        $firstNovelty = factory(Novelty::class)->create([ // scheduled novelty from other time clock log
            'employee_id' => $employee->id,
            'time_clock_log_id' => $log->id,
            'novelty_type_id' => $noveltyTypes->firstWhere('code', 'HADI')->id,
            'start_at' => '2020-05-20 14:49:00',
            'end_at' => '2020-05-20 16:00:00',
        ]);

        $secondNovelty = factory(Novelty::class)->create([
            'employee_id' => $employee->id,
            'time_clock_log_id' => $log->id,
            'novelty_type_id' => $noveltyTypes->firstWhere('code', 'HN')->id, // normal work shift novelty time
            'start_at' => '2020-05-20 11:27:00',
            'end_at' => '2020-05-20 14:49:00',
        ]);

        // set setting to adjust scheduled novelties time
        $this->seed(TimeClockSettingsSeeder::class);

        Carbon::setTestNow(Carbon::create(2020, 05, 20, 16, 06, 00));
        $requestData = [
            'identification_code' => $employee->identifications->first()->code,
            'work_shift_id' => $workShift->id,
        ];
        $this->json('POST', $this->endpoint, $requestData)
            ->assertCreated();

        $this->assertDatabaseHas('novelties', [ // normal work shift novelty time should NOT be changed
            'id' => $secondNovelty->id,
            'novelty_type_id' => $noveltyTypes->firstWhere('code', 'HN')->id,
            'start_at' => '2020-05-20 11:27:00',
            'end_at' => '2020-05-20 14:49:00',
        ]);

        $this->assertDatabaseHas('novelties', [ // scheduled novelty should be be changed
            'id' => $firstNovelty->id,
            'novelty_type_id' => $noveltyTypes->firstWhere('code', 'HADI')->id,
            'start_at' => '2020-05-20 14:49:00',
            'end_at' => '2020-05-20 16:06:00',
        ]);
    }

    /**
     * @test
     */
    public function shouldNotSetDefaultNoveltyWhenArrivesOnTimeForScheduledNovelty()
    {
        // set setting to NOT require novelty type when check in is too late,
        // this make to set a default novelty type id for the late check in
        $this->seed(TimeClockSettingsSeeder::class);

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 18',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']], // should check in at 7am
            ])->create();

        // fake current date time, one hour late for work shift, on time for
        // scheduled novelty
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 8, 00));

        // create scheduled novelty, this will make not to set the default
        // novelty for the late check in
        $noveltyData = [
            'employee_id' => $employee->id,
            'start_at' => now()->subHour(),
            'end_at' => now(),
        ];

        factory(Novelty::class)->create($noveltyData);

        $requestData = ['identification_code' => $employee->identifications->first()->code];
        $this->json('POST', $this->endpoint, $requestData)
            ->assertCreated()
            ->assertJsonHasPath('data.id');

        $expectedTimeClockLog = [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->first()->id,
            'check_in_novelty_type_id' => null,
        ];

        $this->assertDatabaseHas('time_clock_logs', $expectedTimeClockLog);
    }

    /**
     * @test
     */
    public function shouldSetDefaultNoveltyWhenArrivesTooLateForScheduledNovelty()
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
        $this->seed(TimeClockSettingsSeeder::class);
        Setting::where(['key' => 'time-clock.adjust-scheduled-novelty-datetime-based-on-checks'])->update(['value' => false]);

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

        $this->json('POST', $this->endpoint, $requestData)
            ->assertCreated()
            ->assertJsonHasPath('data.id');

        $expectedTimeClockLog = [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->first()->id,
            'check_in_novelty_type_id' => $this->subtractTimeNovelty->id,
        ];

        $this->assertDatabaseHas('time_clock_logs', $expectedTimeClockLog);
    }

    /**
     * @test
     */
    public function shouldUpdateScheduledNoveltyEndDateWhenArrivesTooLateToSaidNoveltyEnd()
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
        $this->seed(TimeClockSettingsSeeder::class);

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

        $this->json('POST', $this->endpoint, $requestData)
            ->assertCreated()
            ->assertJsonHasPath('data.id');

        $expectedTimeClockLog = [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->first()->id,
            'check_in_novelty_type_id' => null,
        ];

        $this->assertDatabaseRecordsCount(1, 'time_clock_logs', ['employee_id' => $employee->id]);
        $this->assertDatabaseHas('time_clock_logs', $expectedTimeClockLog);

        $this->assertDatabaseRecordsCount(1, 'novelties', ['employee_id' => $employee->id]);
        $this->assertDatabaseHas('novelties', [
            'id' => $scheduledNovelty->id,
            'start_at' => now()->setTime(7, 0),
            'end_at' => now()->setTime(9, 0),
        ]);
    }

    /**
     * @test
     */
    public function shouldNotUpdateScheduledNoveltyEndDateWhenArrivesTooLateToWorkShift()
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
        $this->seed(TimeClockSettingsSeeder::class);

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

        $this->json('POST', $this->endpoint, $requestData)
            ->assertCreated()
            ->assertJsonHasPath('data.id');

        $expectedTimeClockLog = [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->first()->id,
            'check_in_novelty_type_id' => $this->subtractTimeNovelty->id,
        ];

        $this->assertDatabaseHas('time_clock_logs', $expectedTimeClockLog);
        // scheduled novelty should not be updated
        $this->assertDatabaseHas('novelties', [
            'id' => $scheduledNovelty->id,
            'start_at' => now()->setTime(10, 0)->toDateTimeString(), // 10am
            'end_at' => now()->setTime(11, 0)->toDateTimeString(), // 11am
        ]);
    }

    /**
     * @test
     */
    public function shouldIgnoreCheckoutScheduledNovelties()
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
        $this->seed(TimeClockSettingsSeeder::class);
        Setting::where(['key' => 'time-clock.adjust-scheduled-novelty-datetime-based-on-checks'])->update(['value' => false]);

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

        $this->json('POST', $this->endpoint, $requestData)
            ->assertCreated()
            ->assertJsonHasPath('data.id');

        $expectedTimeClockLog = [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->first()->id,
            'check_in_novelty_type_id' => null,
        ];

        $this->assertDatabaseHas('time_clock_logs', $expectedTimeClockLog);
        // scheduled novelty should not be affected
        $this->assertDatabaseHas('novelties', $noveltyData);
    }

    /**
     * @test
     */
    public function shouldBeAwareOfScheduledNoveltyOnTheMiddleOfWorkShift()
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
        $this->seed(TimeClockSettingsSeeder::class);

        // create scheduled novelty from 10am to 12m, employee has checked in at
        // 7am and checked out at 10am because the scheduled novelty from 10am to
        // 12m, then the employee arrives at 12m, he's on time to check in to
        // finish the remaining work shift time
        $noveltyData = [
            'employee_id' => $employee->id,
            'time_clock_log_id' => 1,
            'start_at' => now()->setTime(10, 00), // 10am
            'end_at' => now()->setTime(12, 00), // 12m
        ];

        factory(Novelty::class)->create($noveltyData);

        $requestData = [
            'identification_code' => $employee->identifications->first()->code,
        ];

        $this->json('POST', $this->endpoint, $requestData)
            ->assertCreated()
            ->assertJsonHasPath('data.id');

        $expectedTimeClockLog = [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->first()->id,
            'checked_in_at' => now()->toDateTimeString(),
            'check_in_novelty_type_id' => null,
        ];

        $this->assertDatabaseHas('time_clock_logs', $expectedTimeClockLog);
    }

    /**
     * @test
     */
    public function shouldFixScheduledNoveltyEndTimeWhenArrivesToEarlyToSaisNoveltyAndHasMorningCheckIn()
    {
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

        // set setting to NOT require novelty type when check in is too late and
        // set a default novelty type for the early/late check in/outs
        $this->seed(TimeClockSettingsSeeder::class);

        // fake current date time, monday at 10:30am, too early because of scheduled novelty end at 12m
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 10, 30));

        // create scheduled novelty from 10am to 12m, employee has checked in at
        // 7am and checked out at 10am because the scheduled novelty from 10am to
        // 12m, then the employee arrives at 10:30am, he's too early to check in
        // because scheduled novelty end is until 12m
        $noveltyData = [
            'employee_id' => $employee->id,
            'time_clock_log_id' => 1,
            'start_at' => now()->setTime(10, 00), // 10am
            'end_at' => now()->setTime(12, 00), // 12m
        ];

        $scheduledNovelty = factory(Novelty::class)->create($noveltyData);

        $requestData = [
            'identification_code' => $employee->identifications->first()->code,
        ];

        $this->json('POST', $this->endpoint, $requestData)
            ->assertCreated()
            ->assertJsonHasPath('data.id');

        $expectedTimeClockLog = [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->first()->id,
            'checked_in_at' => now()->toDateTimeString(),
            'check_in_novelty_type_id' => null,
        ];

        $this->assertDatabaseHas('time_clock_logs', $expectedTimeClockLog);
        // scheduled novelty end time should be fixed to the check in time
        $this->assertDatabaseHas('novelties', [
            'id' => $scheduledNovelty->id,
            'start_at' => now()->setTime(10, 00), // 10pm, unchanged
            'end_at' => now()->setTime(10, 30), // 10:30am, last check in time
        ]);
    }

    // ######################################################################## #
    //             Automatic novelty deduction on eager/late check in           #
    // ######################################################################## #

    /**
     * @test
     */
    public function whenHasSingleWorkShiftAndArrivesTooLateButNoveltyIsNotRequired()
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
        $this->seed(TimeClockSettingsSeeder::class);

        $requestData = [
            'identification_code' => $employee->identifications->first()->code,
        ];

        $this->json('POST', $this->endpoint, $requestData)
            ->assertCreated()
            ->assertJsonHasPath('data.id');

        $this->assertDatabaseHas('time_clock_logs', [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->first()->id,
            'check_in_novelty_type_id' => $this->subtractTimeNovelty->id,
        ]);
    }

    /**
     * @test
     */
    public function whenHasSingleWorkShiftAndArrivesTooEarlyButNoveltyIsNotRequired()
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
        $this->seed(TimeClockSettingsSeeder::class);

        $requestData = [
            'identification_code' => $employee->identifications->first()->code,
        ];

        $this->json('POST', $this->endpoint, $requestData)
            ->assertCreated()
            ->assertJsonHasPath('data.id');

        $this->assertDatabaseHas('time_clock_logs', [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->first()->id,
            'check_in_novelty_type_id' => $this->additionalTimeNovelty->id,
        ]);
    }

    /**
     * @test
     */
    public function whenEmployeeHasTwoWorkShiftsAndNoveltyIsNotRequiredShouldNotReturnNoveltyTypes()
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
            ]]);

        $employee->workShifts()->attach($novelty);

        // fake current date time, one hour late
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 14, 57));

        // set setting to NOT require novelty type when check in is too late
        $this->seed(TimeClockSettingsSeeder::class);

        $requestData = [
            'identification_code' => $employee->identifications->first()->code,
        ];

        $this->json('POST', $this->endpoint, $requestData)
            ->assertStatus(422)
            ->assertJsonMissingPath('errors.0.meta.novelty_types.0')
            ->assertJsonMissingPath('errors.0.meta.novelty_types.1');
    }

    // ######################################################################## #
    //                            Permissions tests                             #
    // ######################################################################## #

    /**
     * @test
     */
    public function shouldReturnForbidenWhenUserDoesntHaveRequiredPermissions()
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();

        $this->json('POST', $this->endpoint, [])
            ->assertForbidden();
    }
}
