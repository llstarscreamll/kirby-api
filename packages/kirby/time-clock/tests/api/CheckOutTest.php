<?php

namespace Kirby\TimeClock\Tests\api;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Kirby\Company\Models\SubCostCenter;
use Kirby\Employees\Models\Employee;
use Kirby\Novelties\Enums\DayType;
use Kirby\Novelties\Enums\NoveltyTypeOperator;
use Kirby\Novelties\Models\Novelty;
use Kirby\Novelties\Models\NoveltyType;
use Kirby\TimeClock\Events\CheckedOutEvent;
use Kirby\TimeClock\Models\Setting;
use TimeClockPermissionsSeeder;

/**
 * Class CheckOutTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CheckOutTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/time-clock/check-out';

    /**
     * @var \Kirby\Users\Models\User
     */
    private $user;

    /**
     * @var \Kirby\Company\Models\SubCostCenter
     */
    private $firstSubCostCenter;

    /**
     * @var \Kirby\Company\Models\SubCostCenter
     */
    private $secondSubCostCenter;

    public function setUp(): void
    {
        parent::setUp();
        Artisan::call('db:seed', ['--class' => TimeClockPermissionsSeeder::class]);
        $this->actingAsAdmin($this->user = factory(\Kirby\Users\Models\User::class)->create());
        $this->firstSubCostCenter = factory(SubCostCenter::class)->create();
        $this->secondSubCostCenter = factory(SubCostCenter::class)->create();

        // novelty types
        factory(NoveltyType::class, 2)->create([
            'operator' => NoveltyTypeOperator::Subtraction,
            'apply_on_days_of_type' => null,
            'context_type' => 'elegible_by_user',
        ]);

        factory(NoveltyType::class)->create([
            'code' => 'HADI',
            'operator' => NoveltyTypeOperator::Addition,
            'apply_on_days_of_type' => null,
            'context_type' => 'elegible_by_user',
        ]);

        factory(NoveltyType::class)->create([
            'operator' => NoveltyTypeOperator::Subtraction, 'code' => 'PP',
            'apply_on_days_of_type' => null,
        ]);
    }

    /**
     * @test
     */
    public function whenCheckInHasNotShift()
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 18, 00));
        $checkedInTime = now()->setTime(7, 0);

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('timeClockLogs', [
                'work_shift_id' => null, // empty shift
                'check_in_novelty_type_id' => 3, // empty shift must specify addition novelty type
                'check_in_sub_cost_center_id' => $this->secondSubCostCenter->id, // addition novelty type must provide related sub cost center
                'checked_in_at' => $checkedInTime,
                'checked_out_at' => null,
                'checked_in_by_id' => $this->user->id,
            ])
            ->create();

        // check in novelty type and check in sub cost center already exists,
        // only the identification code is required
        $requestData = [
            'identification_code' => $employee->identifications->first()->code,
        ];

        $this->expectsEvents(CheckedOutEvent::class);

        $this->json('POST', $this->endpoint, $requestData)
            ->assertOk()
            ->assertJsonHasPath('data.id');

        $this->assertDatabaseHas('time_clock_logs', [
            'employee_id' => $employee->id,
            'work_shift_id' => null,
            'sub_cost_center_id' => null,
            'check_in_novelty_type_id' => 3,
            'check_in_sub_cost_center_id' => $this->secondSubCostCenter->id,
            'checked_in_at' => $checkedInTime->toDateTimeString(),
            'checked_out_at' => now()->toDateTimeString(),
            'expected_check_out_at' => null,
            'checked_in_by_id' => $this->user->id,
            'checked_out_by_id' => $this->user->id,
        ]);
    }

    /**
     * @test
     */
    public function whenCheckInHasNotShiftAndHasNotSubCostCenter()
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 18, 00));
        $checkedInTime = now()->setTime(7, 0);

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('timeClockLogs', [ // check in with novelty type but without sub cost center
                'work_shift_id' => null, // empty shift
                'check_in_novelty_type_id' => 3, // some novelty type setted
                'check_in_sub_cost_center_id' => null, // empty sub cost center
                'checked_in_at' => $checkedInTime,
                'checked_out_at' => null,
                'checked_in_by_id' => $this->user->id,
            ])
            ->create();

        // missing sub cost center field
        $requestData = [
            'identification_code' => $employee->identifications->first()->code,
        ];

        $this->json('POST', $this->endpoint, $requestData)
            ->assertStatus(422) // error, sub cost center is required
            ->assertJsonHasPath('errors.0.meta.sub_cost_centers');
    }

    /**
     * @test
     */
    public function whenHasShiftAndLeavesOnTime()
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 18, 00));
        $checkedInTime = now()->setTime(7, 0);

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 6',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']],
            ])
            ->with('timeClockLogs', [
                'work_shift_id' => 1,
                'checked_in_at' => $checkedInTime,
                'checked_out_at' => null,
                'checked_in_by_id' => $this->user->id,
            ])
            ->create();

        $requestData = [
            'sub_cost_center_id' => $this->firstSubCostCenter->id,
            'identification_code' => $employee->identifications->first()->code,
        ];

        $this->json('POST', $this->endpoint, $requestData)
            ->assertOk()
            ->assertJsonHasPath('data.id');

        $this->assertDatabaseHas('time_clock_logs', [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->first()->id,
            'sub_cost_center_id' => $this->firstSubCostCenter->id,
            'checked_in_at' => $checkedInTime->toDateTimeString(),
            'checked_out_at' => now()->toDateTimeString(),
            'expected_check_out_at' => now()->setTime(18, 00)->toDateTimeString(),
            'checked_in_by_id' => $this->user->id,
            'checked_out_by_id' => $this->user->id,
        ]);
    }

    /**
     * @test
     */
    public function whenHasNotCheckIn()
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 18, 00));

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->create();

        $requestData = [
            'sub_cost_center_id' => $this->firstSubCostCenter->id,
            'identification_code' => $employee->identifications->first()->code,
        ];

        $this->json('POST', $this->endpoint, $requestData)
            ->assertStatus(422)
            ->assertJsonHasPath('errors.0.code')
            ->assertJsonHasPath('errors.0.title')
            ->assertJsonHasPath('errors.0.detail');
    }

    /**
     * @test
     */
    public function whenHasShiftAndLeavesTooEarly()
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 16, 00));
        $checkedInTime = now()->setTime(7, 0);

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 6',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']],
            ])
            ->with('timeClockLogs', [
                'work_shift_id' => 1,
                'checked_in_at' => $checkedInTime,
                'checked_out_at' => null,
                'checked_in_by_id' => $this->user->id,
            ])
            ->create();

        $requestData = [
            'sub_cost_center_id' => $this->firstSubCostCenter->id,
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
            ->assertJsonHasPath('errors.0.meta.novelty_types.0.id') // should return novelties that subtracts time
            ->assertJsonHasPath('errors.0.meta.novelty_types.1.id')
            ->assertJsonMissingPath('errors.0.meta.novelty_types.2.id');
    }

    /**
     * @test
     */
    public function whenHasShiftAndLeavesTooEarlyButNoveltyTypeIsNotRequired()
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 16, 00));
        $checkedInTime = now()->setTime(7, 0);

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 6',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']],
            ])
            ->with('timeClockLogs', [
                'work_shift_id' => 1,
                'checked_in_at' => $checkedInTime,
                'checked_out_at' => null,
                'checked_in_by_id' => $this->user->id,
            ])
            ->create();

        // set setting to NOT require novelty type when check out is too early
        $this->artisan('db:seed', ['--class' => 'TimeClockSettingsSeeder']);
        Setting::where(['key' => 'time-clock.adjust-scheduled-novelty-datetime-based-on-checks'])->update(['value' => false]);

        $requestData = [
            'sub_cost_center_id' => $this->firstSubCostCenter->id,
            'identification_code' => $employee->identifications->first()->code,
        ];

        $this->json('POST', $this->endpoint, $requestData)
            ->assertOk()
            ->assertJsonHasPath('data.id');

        $this->assertDatabaseHas('time_clock_logs', [
            'employee_id' => $employee->id,
            'sub_cost_center_id' => $this->firstSubCostCenter->id,
            'checked_out_at' => now()->toDateTimeString(),
            'expected_check_out_at' => now()->setTime(18, 00)->toDateTimeString(),
            'check_out_novelty_type_id' => 4,
        ]);
    }

    /**
     * @test
     */
    public function whenHasShiftAndLeavesTooLateButNoveltyTypeIsNotRequired()
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 20, 00));
        $checkedInTime = now()->setTime(7, 0);

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 6',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']],
            ])
            ->with('timeClockLogs', [
                'work_shift_id' => 1,
                'checked_in_at' => $checkedInTime,
                'checked_out_at' => null,
                'checked_in_by_id' => $this->user->id,
            ])
            ->create();

        // set setting to NOT require novelty type when check out is too early
        $this->artisan('db:seed', ['--class' => 'TimeClockSettingsSeeder']);
        Setting::where(['key' => 'time-clock.adjust-scheduled-novelty-datetime-based-on-checks'])->update(['value' => false]);

        $requestData = [
            'sub_cost_center_id' => $this->firstSubCostCenter->id,
            'identification_code' => $employee->identifications->first()->code,
        ];

        $this->json('POST', $this->endpoint, $requestData)
            ->assertOk()
            ->assertJsonHasPath('data.id');

        $this->assertDatabaseHas('time_clock_logs', [
            'employee_id' => $employee->id,
            'sub_cost_center_id' => $this->firstSubCostCenter->id,
            'checked_out_at' => now()->toDateTimeString(),
            'expected_check_out_at' => now()->setTime(18, 00)->toDateTimeString(),
            'check_out_novelty_type_id' => 3,
        ]);
    }

    /**
     * @test
     */
    public function whenHasTooEarlyCheckInWithSelectedWorkShiftButLeavesBeforeShiftStart()
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 06, 50)); // 10 minutes before work shift start
        $checkedInTime = now()->setTime(6, 0); // one hour early check in

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 6',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']],
            ])
            ->with('timeClockLogs', [
                'work_shift_id' => 1,
                'checked_in_at' => $checkedInTime, // one hour early check in
                'check_in_novelty_type_id' => 3,
                'check_in_sub_cost_center_id' => $this->firstSubCostCenter->id,
                'checked_out_at' => null,
                'check_out_novelty_type_id' => null,
                'checked_in_by_id' => $this->user->id,
            ])
            ->create();

        $requestData = [
            // sub cost center is not required because no work shift time will be registered
            'novelty_type_id' => 1, // subtract novelty type, because missing work shift time
            'identification_code' => $employee->identifications->first()->code,
        ];

        $this->json('POST', $this->endpoint, $requestData)
            ->assertOk()
            ->assertJsonHasPath('data.id');

        $this->assertDatabaseHas('time_clock_logs', [
            'employee_id' => $employee->id,
            'sub_cost_center_id' => null,
            'expected_check_out_at' => now()->setTime(18, 00)->toDateTimeString(),
            'check_out_novelty_type_id' => 1,
            'check_out_sub_cost_center_id' => null,
        ]);
    }

    /**
     * @test
     */
    public function whenHasShiftAndLeavesTooLate()
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 18, 30));
        $checkedInTime = now()->setTime(7, 0);

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 6',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']],
            ])
            ->with('timeClockLogs', [
                'work_shift_id' => 1,
                'checked_in_at' => $checkedInTime,
                'checked_out_at' => null,
                'checked_in_by_id' => $this->user->id,
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

        $requestData = [
            'sub_cost_center_id' => $this->firstSubCostCenter->id,
            'identification_code' => $employee->identifications->first()->code,
        ];

        $this->json('POST', $this->endpoint, $requestData)
            ->assertStatus(422)
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
            ->assertJsonPath('errors.0.meta.novelty_types.0.id', $expectedNoveltyType->id) // should return expected novelty type according to day type and time ranges
            ->assertJsonMissingPath('errors.0.meta.novelty_types.1.id')
            ->assertJsonMissingPath('errors.0.meta.novelty_types.2.id')
            ->assertJsonFragment(['code' => 1053]);
    }

    /**
     * @test
     */
    public function whenHasShiftAndLeavesTooLateWithRightNoveltyType()
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 18, 30)); // 30 minutes late
        $checkedInTime = now()->setTime(7, 0);

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 6',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']],
            ])
            ->with('timeClockLogs', [
                'work_shift_id' => 1,
                'checked_in_at' => $checkedInTime,
                'checked_out_at' => null,
                'check_in_novelty_type_id' => 1, // with check in novelty type
                'checked_in_by_id' => $this->user->id,
            ])
            ->create();

        $requestData = [
            'novelty_type_id' => 3, // addition novelty type
            'novelty_sub_cost_center_id' => $this->secondSubCostCenter->id, // sub cost center because novelty type
            'sub_cost_center_id' => $this->firstSubCostCenter->id,
            'identification_code' => $employee->identifications->first()->code,
        ];

        $this->json('POST', $this->endpoint, $requestData)
            ->assertOk()
            ->assertJsonHasPath('data.id');

        $this->assertDatabaseHas('time_clock_logs', [
            'employee_id' => $employee->id,
            'expected_check_out_at' => now()->setTime(18, 00)->toDateTimeString(),
            'sub_cost_center_id' => $this->firstSubCostCenter->id,
            'check_out_novelty_type_id' => 3,
            'check_out_sub_cost_center_id' => $this->secondSubCostCenter->id,
        ]);
    }

    /**
     * @test
     */
    public function whenHasShiftAndLeavesTooLateWithWrongNoveltyType()
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 18, 30));
        $checkedInTime = now()->setTime(7, 0);

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 6',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']],
            ])
            ->with('timeClockLogs', [
                'work_shift_id' => 1,
                'checked_in_at' => $checkedInTime,
                'checked_out_at' => null,
                'check_in_novelty_type_id' => 1, // with check in novelty type
                'checked_in_by_id' => $this->user->id,
            ])
            ->create();

        $requestData = [
            'novelty_type_id' => 1, // wrong subtraction novelty type
            'sub_cost_center_id' => $this->firstSubCostCenter->id,
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
            ->assertJsonPath('errors.0.meta.novelty_types.0.id', 3) // should return addition novelty types
            ->assertJsonMissingPath('errors.0.meta.novelty_types.1.id')
            ->assertJsonMissingPath('errors.0.meta.novelty_types.2.id');
    }

    /**
     * @test
     */
    public function whenSubCostCenterDoesNotExists()
    {
        // fake current date time
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 18, 00));
        $checkedInTime = now()->setTime(7, 0);

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('timeClockLogs', [
                'work_shift_id' => null, // empty shift
                'checked_in_at' => $checkedInTime,
                'checked_out_at' => null,
                'checked_in_by_id' => $this->user->id,
            ])
            ->create();

        $requestData = [
            'sub_cost_center_id' => 100,
            'identification_code' => $employee->identifications->first()->code,
        ];

        $this->doesntExpectEvents(CheckedOutEvent::class);

        $this->json('POST', $this->endpoint, $requestData)
            ->assertStatus(422)
            ->assertJsonHasPath('message')
            ->assertJsonHasPath('errors.sub_cost_center_id.0');
    }

    /**
     * @test
     */
    public function whenSubCostCenterIsMissing()
    {
        // fake current date time, monday 6:00pm, on time to check out
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 18, 00));
        $checkedInTime = now()->setTime(7, 0);

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 6',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']],
            ])
            ->with('timeClockLogs', [
                'work_shift_id' => 1,
                'checked_in_at' => $checkedInTime, // on time
                'checked_out_at' => null,
                'check_in_novelty_type_id' => null,
                'checked_in_by_id' => $this->user->id,
            ])
            ->create();

        $requestData = [
            'identification_code' => $employee->identifications->first()->code,
        ];

        $this->json('POST', $this->endpoint, $requestData)
            ->assertStatus(422)
            ->assertJsonFragment(['code' => 1056]) // MissingSubCostCenterException
            ->assertJsonHasPath('errors.0.code')
            ->assertJsonHasPath('errors.0.title')
            ->assertJsonHasPath('errors.0.detail')
            ->assertJsonHasPath('errors.0.meta')
            ->assertJsonHasPath('errors.0.meta.action')
            ->assertJsonHasPath('errors.0.meta.employee')
            ->assertJsonHasPath('errors.0.meta.punctuality')
            ->assertJsonHasPath('errors.0.meta.work_shifts')
            ->assertJsonHasPath('errors.0.meta.novelty_types')
        // no novelties, because employee is on time
            ->assertJsonMissingPath('errors.0.meta.novelty_types.0')
            ->assertJsonHasPath('errors.0.meta.sub_cost_centers');
    }

    // ######################################################################## #
    //                         Scheduled novelties tests                        #
    // ######################################################################## #

    /**
     * @test
     */
    public function whenHasShiftAndLeavesOnTimeWithScheduledNovelty()
    {
        // fake current date time, monday at 5pm
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 17, 00));
        $checkedInTime = now()->setTime(7, 00);

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 6',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']], // should check out at 6pm
            ])
            ->with('timeClockLogs', [
                'work_shift_id' => 1,
                'checked_in_at' => $checkedInTime,
                'checked_out_at' => null,
                'check_in_novelty_type_id' => null,
                'checked_in_by_id' => $this->user->id,
            ])
            ->create();

        // create scheduled novelty from 5pm to 6pm, since employee leaves at
        // 5pm, he's on time to check out, so the default novelty type for check
        // out should not be setted
        $noveltyData = [
            'employee_id' => $employee->id,
            'start_at' => now()->setTime(17, 00),
            'end_at' => now()->setTime(18, 00),
        ];

        factory(Novelty::class)->create($noveltyData);

        $requestData = [
            'sub_cost_center_id' => $this->firstSubCostCenter->id,
            'identification_code' => $employee->identifications->first()->code,
        ];

        $this->json('POST', $this->endpoint, $requestData)
            ->assertOk()
            ->assertJsonHasPath('data.id');

        $this->assertDatabaseHas('time_clock_logs', [
            'employee_id' => $employee->id,
            'expected_check_out_at' => now()->setTime(17, 00)->toDateTimeString(),
            'sub_cost_center_id' => $this->firstSubCostCenter->id,
            'check_out_novelty_type_id' => null,
            'check_out_sub_cost_center_id' => null,
        ]);
    }

    /**
     * @test
     */
    public function whenHasShiftAndLeavesTooEarlyWithScheduledNovelty()
    {
        // fake current date time, monday at 4pm
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 16, 00));
        $checkedInTime = now()->setTime(7, 00);

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 6',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']], // should check out at 6pm
            ])
            ->with('timeClockLogs', [
                'work_shift_id' => 1,
                'checked_in_at' => $checkedInTime,
                'checked_out_at' => null,
                'check_in_novelty_type_id' => null,
                'checked_in_by_id' => $this->user->id,
            ])
            ->create();

        // set setting to NOT require novelty type when check out is too early,
        // this make to set a default novelty type id for the early check out
        $this->artisan('db:seed', ['--class' => 'TimeClockSettingsSeeder']);
        Setting::where(['key' => 'time-clock.adjust-scheduled-novelty-datetime-based-on-checks'])->update(['value' => false]);

        // create scheduled novelty from 5pm to 6pm, since employee leaves at
        // 4pm, he's too early to check out, so the default novelty type for
        // early check out should be setted
        $noveltyData = [
            'employee_id' => $employee->id,
            'start_at' => now()->setTime(17, 00),
            'end_at' => now()->setTime(18, 00),
        ];

        factory(Novelty::class)->create($noveltyData);

        $requestData = [
            'sub_cost_center_id' => $this->firstSubCostCenter->id,
            'identification_code' => $employee->identifications->first()->code,
        ];

        $this->json('POST', $this->endpoint, $requestData)
            ->assertOk()
            ->assertJsonHasPath('data.id');

        $this->assertDatabaseHas('time_clock_logs', [
            'employee_id' => $employee->id,
            'expected_check_out_at' => now()->setTime(17, 00)->toDateTimeString(),
            'sub_cost_center_id' => $this->firstSubCostCenter->id,
            'check_out_novelty_type_id' => 4,
            'check_out_sub_cost_center_id' => $this->firstSubCostCenter->id,
        ]);
    }

    /**
     * @test
     */
    public function shouldUpdateScheduledNoveltyTimesWhenLeavesTooEarlyToSaidNovelty()
    {
        // fake current date time, monday at 4pm
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 16, 00));
        $checkedInTime = now()->setTime(7, 00);

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 6',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']], // should check out at 6pm
            ])
            ->with('timeClockLogs', [
                'work_shift_id' => 1,
                'checked_in_at' => $checkedInTime,
                'checked_out_at' => null,
                'check_in_novelty_type_id' => null,
                'checked_in_by_id' => $this->user->id,
            ])
            ->create();

        // set setting to NOT require novelty type when check out is too early,
        // this make to set a default novelty type id for the early check out
        $this->artisan('db:seed', ['--class' => 'TimeClockSettingsSeeder']);

        // create scheduled novelty from 5pm to 6pm, since employee leaves at
        // 4pm, he's too early to check out
        $noveltyData = [
            'employee_id' => $employee->id,
            'start_at' => now()->setTime(17, 00),
            'end_at' => now()->setTime(18, 00),
        ];

        $scheduledNovelty = factory(Novelty::class)->create($noveltyData);

        $requestData = [
            'sub_cost_center_id' => $this->firstSubCostCenter->id,
            'identification_code' => $employee->identifications->first()->code,
        ];

        $this->json('POST', $this->endpoint, $requestData)
            ->assertOk()
            ->assertJsonHasPath('data.id');

        $this->assertDatabaseHas('time_clock_logs', [
            'employee_id' => $employee->id,
            'expected_check_out_at' => now(),
            'sub_cost_center_id' => $this->firstSubCostCenter->id,
            'check_out_novelty_type_id' => null,
            'check_out_sub_cost_center_id' => null,
        ]);
        $this->assertDatabaseHas('novelties', [
            'id' => $scheduledNovelty->id,
            'start_at' => now(),
        ]);
    }

    /**
     * @test
     */
    public function whenHasShiftAndLeavesOnTimeShouldIgnoreCheckInScheduledNovelty()
    {
        // fake current date time, monday at 6pm
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 18, 00));
        $checkedInTime = now()->setTime(8, 00);

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 6',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']], // should check out at 6pm
            ])
            ->with('timeClockLogs', [
                'work_shift_id' => 1,
                'checked_in_at' => $checkedInTime,
                'checked_out_at' => null,
                'check_in_novelty_type_id' => null,
                'checked_in_by_id' => $this->user->id,
            ])
            ->create();

        // set setting to NOT require novelty type when check out is too early,
        // this make to set a default novelty type id for the early check out
        $this->artisan('db:seed', ['--class' => 'TimeClockSettingsSeeder']);
        Setting::where(['key' => 'time-clock.adjust-scheduled-novelty-datetime-based-on-checks'])->update(['value' => false]);

        // create scheduled novelty from 7am to 8am, since employee leaves at
        // 6pm, he's on time to check out, scheduled novelty has no effect in
        // this scenario because of out of time range from said novelty
        $noveltyData = [
            'employee_id' => $employee->id,
            // The novelty should be attached to a time clock log because it's a
            // past tense record
            'time_clock_log_id' => $employee->timeClockLogs->first()->id,
            'start_at' => now()->setTime(7, 00),
            'end_at' => now()->setTime(8, 00),
        ];

        factory(Novelty::class)->create($noveltyData);

        $requestData = [
            'sub_cost_center_id' => $this->firstSubCostCenter->id,
            'identification_code' => $employee->identifications->first()->code,
        ];

        $this->json('POST', $this->endpoint, $requestData)
            ->assertOk()
            ->assertJsonHasPath('data.id');

        $this->assertDatabaseHas('time_clock_logs', [
            'employee_id' => $employee->id,
            'expected_check_out_at' => now()->setTime(18, 00)->toDateTimeString(),
            'sub_cost_center_id' => $this->firstSubCostCenter->id,
            'check_out_novelty_type_id' => null,
            'check_out_sub_cost_center_id' => null,
        ]);
    }

    // ######################################################################## #
    //            Automatic novelty deduction on eager/late check out          #
    // ######################################################################## #

    /**
     * @test
     */
    public function whenHasShifAndSubCostCenterIsMissingAndLeavesTooEarlyAndNoveltiesAreNotRequiredShouldNotReturnNoveltyTypes()
    {
        // fake current date time, monday at 4pm, too early
        Carbon::setTestNow(Carbon::create(2019, 04, 01, 16, 00));
        $checkedInTime = now()->setTime(7, 00);

        $employee = factory(Employee::class)
            ->with('identifications', ['name' => 'card', 'code' => 'fake-employee-card-code'])
            ->with('workShifts', [
                'name' => '7 to 6',
                'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
                'time_slots' => [['start' => '07:00', 'end' => '18:00']], // should check out at 6pm
            ])
            ->with('timeClockLogs', [
                'work_shift_id' => 1,
                'checked_in_at' => $checkedInTime,
                'checked_out_at' => null,
                'check_in_novelty_type_id' => null,
                'checked_in_by_id' => $this->user->id,
            ])
            ->create();

        // set setting to NOT require novelty type when check out is too early,
        // this make to set a default novelty type id for the early check out
        $this->artisan('db:seed', ['--class' => 'TimeClockSettingsSeeder']);
        Setting::where(['key' => 'time-clock.adjust-scheduled-novelty-datetime-based-on-checks'])->update(['value' => false]);

        $requestData = [
            'sub_cost_center_id' => null, // without sub cost center!!
            'identification_code' => $employee->identifications->first()->code,
        ];

        $this->json('POST', $this->endpoint, $requestData)
            ->assertStatus(422)
            ->assertJsonMissingPath('errors.0.meta.novelty_types.0')
            ->assertJsonMissingPath('errors.0.meta.novelty_types.1');
    }

    // ######################################################################## #
    //                            Permissions tests                            #
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
