<?php

namespace ClockTime;

use Illuminate\Support\Carbon;
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
    private $subCostCenter;

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        $I->disableMiddleware();
        $this->user = $I->amLoggedAsAdminUser();
        $I->haveHttpHeader('Accept', 'application/json');

        // novelty types
        factory(NoveltyType::class, 2)->create([
            'operator' => NoveltyTypeOperator::Subtraction,
        ]);

        factory(NoveltyType::class)->create([
            'operator' => NoveltyTypeOperator::Addition,
        ]);

        $this->subCostCenter = factory(SubCostCenter::class)->create();
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
                'checked_in_at' => $checkedInTime,
                'checked_out_at' => null,
                'checked_in_by_id' => $this->user->id,
            ])
            ->create();

        $requestData = [
            'sub_cost_center' => ['id' => $this->subCostCenter->id],
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(200);
        $I->seeEventTriggered(CheckedOutEvent::class);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('time_clock_logs', [
            'employee_id' => $employee->id,
            'work_shift_id' => null,
            'sub_cost_center_id' => $this->subCostCenter->id,
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
            'sub_cost_center' => ['id' => $this->subCostCenter->id],
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('time_clock_logs', [
            'employee_id' => $employee->id,
            'work_shift_id' => $employee->workShifts->first()->id,
            'sub_cost_center_id' => $this->subCostCenter->id,
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
            'sub_cost_center' => ['id' => $this->subCostCenter->id],
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
            'sub_cost_center' => ['id' => $this->subCostCenter->id],
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

        $requestData = [
            'sub_cost_center' => ['id' => $this->subCostCenter->id],
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
        // should return novelties that adds time
        $I->seeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types.0.id');
        $I->dontSeeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types.1.id');
        $I->dontSeeResponseJsonMatchesJsonPath('$.errors.0.meta.novelty_types.2.id');
        $I->seeResponseContainsJson(['code' => 1053]);
        $I->dontSeeResponseContainsJson(['novelty_types' => ['id' => 1]]);
        $I->dontSeeResponseContainsJson(['novelty_types' => ['id' => 2]]);
        $I->seeResponseContainsJson(['novelty_types' => ['id' => 3]]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenHasShiftAndLeavesTooLateWithRightNoveltyType(ApiTester $I)
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
            'novelty_type' => ['id' => 3], // addition novelty type
            'sub_cost_center' => ['id' => $this->subCostCenter->id],
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('time_clock_logs', [
            'employee_id' => $employee->id,
            'check_in_novelty_type_id' => 1,
            'check_out_novelty_type_id' => 3,
            'sub_cost_center_id' => $this->subCostCenter->id,
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
            'novelty_type' => ['id' => 1], // wrong subtraction novelty type
            'sub_cost_center' => ['id' => $this->subCostCenter->id],
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
            'sub_cost_center' => ['id' => 100],
            'identification_code' => $employee->identifications->first()->code,
        ];

        $I->sendPOST($this->endpoint, $requestData);

        $I->seeResponseCodeIs(422);
        $I->dontSeeEventTriggered(CheckedOutEvent::class);
        $I->seeResponseJsonMatchesJsonPath('$.message');
        $I->seeResponseJsonMatchesJsonPath('$.errors.["sub_cost_center.id"].0');
    }
}
