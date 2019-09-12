<?php

namespace ClockTime;

use Illuminate\Support\Carbon;
use TimeClockPermissionsSeeder;
use Illuminate\Support\Facades\Artisan;
use llstarscreamll\Novelties\Enums\DayType;
use llstarscreamll\Novelties\Models\Novelty;
use llstarscreamll\Employees\Models\Employee;
use llstarscreamll\Company\Models\SubCostCenter;
use llstarscreamll\Novelties\Models\NoveltyType;
use llstarscreamll\TimeClock\Events\CheckedOutEvent;
use llstarscreamll\Novelties\Enums\NoveltyTypeOperator;

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
     * @var \llstarscreamll\Company\Models\SubCostCenter
     */
    private $firstSubCostCenter;

    /**
     * @var \llstarscreamll\Company\Models\SubCostCenter
     */
    private $secondSubCostCenter;

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        $I->disableMiddleware();

        Artisan::call('db:seed', ['--class' => TimeClockPermissionsSeeder::class]);
        $this->user = $I->amLoggedAsAdminUser();
        $this->firstSubCostCenter = factory(SubCostCenter::class)->create();
        $this->secondSubCostCenter = factory(SubCostCenter::class)->create();

        // novelty types
        factory(NoveltyType::class, 2)->create([
            'operator' => NoveltyTypeOperator::Subtraction,
            'context_type' => 'elegible_by_user',
        ]);

        factory(NoveltyType::class)->create([
            'code' => 'HADI',
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
    public function whenCheckInHasNotShift(ApiTester $I)
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

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(200);
        $I->seeEventTriggered(CheckedOutEvent::class);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('time_clock_logs', [
            'employee_id' => $employee->id,
            'work_shift_id' => null,
            'sub_cost_center_id' => null,
            'check_in_novelty_type_id' => 3,
            'check_in_sub_cost_center_id' => $this->secondSubCostCenter->id,
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
    public function whenHasShiftAndLeavesOnTime(ApiTester $I)
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

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('time_clock_logs', [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->first()->id,
            'sub_cost_center_id' => $this->firstSubCostCenter->id,
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
    public function whenHasNotCheckIn(ApiTester $I)
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

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(422);
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.code');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.title');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.detail');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenHasShiftAndLeavesTooEarly(ApiTester $I)
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
        // should return novelties that subtracts time
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types.0.id');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types.1.id');
        $I->dontSeeResponseContainsJson(['novelty_types' => ['id' => 3]]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenHasShiftAndLeavesTooEarlyButNoveltyTypeIsNotRequired(ApiTester $I)
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
        $I->callArtisan('db:seed', ['--class' => 'TimeClockSettingsSeeder']);

        $requestData = [
            'sub_cost_center_id' => $this->firstSubCostCenter->id,
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('time_clock_logs', [
            'employee_id' => $employee->id,
            'sub_cost_center_id' => $this->firstSubCostCenter->id,
            'checked_out_at' => now()->toDateTimeString(),
            'check_out_novelty_type_id' => 4,
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenHasShiftAndLeavesTooLateButNoveltyTypeIsNotRequired(ApiTester $I)
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
        $I->callArtisan('db:seed', ['--class' => 'TimeClockSettingsSeeder']);

        $requestData = [
            'sub_cost_center_id' => $this->firstSubCostCenter->id,
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('time_clock_logs', [
            'employee_id' => $employee->id,
            'sub_cost_center_id' => $this->firstSubCostCenter->id,
            'checked_out_at' => now()->toDateTimeString(),
            'check_out_novelty_type_id' => 3,
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenHasTooEarlyCheckInWithSelectedShiftButLeavesBeforeShiftStart(ApiTester $I)
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
                'checked_in_by_id' => $this->user->id,
            ])
            ->create();

        $requestData = [
            // sub cost center is not required because no work shift time will be registered
            'novelty_type_id' => 1, // subtract novelty type, because missing work shift time
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('time_clock_logs', [
            'employee_id' => $employee->id,
            'sub_cost_center_id' => null,
            'check_out_novelty_type_id' => 1,
            'check_out_sub_cost_center_id' => null,
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenHasShiftAndLeavesTooLate(ApiTester $I)
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
                ['start' => '06:00', 'end' => '21:00'],
            ],
            'context_type' => 'elegible_by_user',
        ]);

        // nighttime overtime
        factory(NoveltyType::class)->create([
            'operator' => NoveltyTypeOperator::Addition,
            'apply_on_days_of_type' => DayType::Workday,
            'apply_on_time_slots' => [
                ['start' => '21:00', 'end' => '06:00'],
            ],
            'context_type' => 'elegible_by_user',
        ]);

        // festive daytime overtime
        factory(NoveltyType::class)->create([
            'operator' => NoveltyTypeOperator::Addition,
            'apply_on_days_of_type' => DayType::Holiday,
            'apply_on_time_slots' => [
                ['start' => '06:00', 'end' => '21:00'],
            ],
            'context_type' => 'elegible_by_user',
        ]);

        $requestData = [
            'sub_cost_center_id' => $this->firstSubCostCenter->id,
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(422);
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
        // should return expected novelty type according to day type and time ranges
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types.0.id');
        $I->dontSeeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types.1.id');
        $I->dontSeeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types.2.id');
        $I->seeResponseContainsJson(['code' => 1053]);
        $I->seeResponseContainsJson(['novelty_types' => [['id' => $expectedNoveltyType->id]]]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenHasShiftAndLeavesTooLateWithRightNoveltyType(ApiTester $I)
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

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('time_clock_logs', [
            'employee_id' => $employee->id,
            'sub_cost_center_id' => $this->firstSubCostCenter->id,
            'check_out_novelty_type_id' => 3,
            'check_out_sub_cost_center_id' => $this->secondSubCostCenter->id,
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenHasShiftAndLeavesTooLateWithWrongNoveltyType(ApiTester $I)
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
    public function whenSubCostCenterDoesNotExists(ApiTester $I)
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

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(422);
        $I->dontSeeEventTriggered(CheckedOutEvent::class);
        $I->seeResponseJsonMatchesJsonPath('$.message');
        $I->seeResponseJsonMatchesJsonPath('$.errors.["sub_cost_center_id"].0');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenSubCostCenterIsMissing(ApiTester $I)
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

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['code' => 1056]); // MissingSubCostCenterException
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.code');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.title');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.detail');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.action');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.employee');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.punctuality');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.work_shifts');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types');
        // no novelties, because employee is on time
        $I->dontSeeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types.0');
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.sub_cost_centers');
    }

    // ######################################################################## #
    //                         Scheduled novelties tests                        #
    // ######################################################################## #

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenHasShiftAndLeavesOnTimeWithScheduledNovelty(ApiTester $I)
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

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('time_clock_logs', [
            'employee_id' => $employee->id,
            'sub_cost_center_id' => $this->firstSubCostCenter->id,
            'check_out_novelty_type_id' => null,
            'check_out_sub_cost_center_id' => null,
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenHasShiftAndLeavesTooEarlyWithScheduledNovelty(ApiTester $I)
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
        $I->callArtisan('db:seed', ['--class' => 'TimeClockSettingsSeeder']);

        // create scheduled novelty from 5pm to 6pm, since employee leaves at
        // 4pm, he's too late to check out, so the default novelty type for
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

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('time_clock_logs', [
            'employee_id' => $employee->id,
            'sub_cost_center_id' => $this->firstSubCostCenter->id,
            'check_out_novelty_type_id' => 4,
            'check_out_sub_cost_center_id' => $this->firstSubCostCenter->id,
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenHasShiftAndLeavesOnTimeShouldIgnoreCheckInScheduledNovelty(ApiTester $I)
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
        $I->callArtisan('db:seed', ['--class' => 'TimeClockSettingsSeeder']);

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

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('time_clock_logs', [
            'employee_id' => $employee->id,
            'sub_cost_center_id' => $this->firstSubCostCenter->id,
            'check_out_novelty_type_id' => null,
            'check_out_sub_cost_center_id' => null,
        ]);
    }

    // ####################################################################### #
    //            Automatic novelty deduction on eager/late check out          #
    // ####################################################################### #

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenHasShifAndSubCostCenterIsMissingAndLeavesTooEarlyAndNoveltiesAreNotRequiredShouldNotReturnNoveltyTypes(ApiTester $I)
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
        $I->callArtisan('db:seed', ['--class' => 'TimeClockSettingsSeeder']);

        $requestData = [
            'sub_cost_center_id' => null, // without sub cost center!!
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(422);
        $I->dontSeeResponseJsonMatchesJsonPath("errors.0.meta.novelty_types.0");
        $I->dontSeeResponseJsonMatchesJsonPath("errors.0.meta.novelty_types.1");
    }

    // ####################################################################### #
    //                            Permissions tests                            #
    // ####################################################################### #

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
