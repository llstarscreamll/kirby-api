<?php

namespace ClockTime\Actions;

use ClockTime\IntegrationTester;
use Kirby\Company\Models\SubCostCenter;
use Kirby\Employees\Models\Employee;
use Kirby\Novelties\Models\Novelty;
use Kirby\TimeClock\Actions\GenerateReportByEmployee;
use Kirby\TimeClock\Models\TimeClockLog;
use Kirby\Users\Models\User;

/**
 * Class GenerateReportByEmployeeCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class GenerateReportByEmployeeCest
{
    /**
     * @param IntegrationTester $I
     */
    public function _before(IntegrationTester $I) {}

    /**
     * @param IntegrationTester $I
     */
    public function shouldGenerateMonthlyReportSuccessfully(IntegrationTester $I)
    {
        // report range dates
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();

        $tonyStark = factory(Employee::class)->create();

        // sub cost centers
        $zeroScc = factory(SubCostCenter::class)->create(['name' => '000']);
        $firstScc = factory(SubCostCenter::class)->create(['name' => 'AAA']);
        $secondScc = factory(SubCostCenter::class)->create(['name' => 'BBB']);

        // another employee time clock log in valid time range, this row should
        // be ignored since it's not a time clock log from Tony Stark
        factory(TimeClockLog::class)->create([
            'sub_cost_center_id' => $zeroScc->id,
            'checked_in_at' => $startDate->copy()->setTime(07, 00),
            'checked_out_at' => $startDate->copy()->setTime(18, 00),
            'expected_check_in_at' => $startDate->copy()->setTime(07, 00),
            'expected_check_out_at' => $startDate->copy()->setTime(18, 00),
        ]);

        // out of range time clock log, this row should be ignored since is out
        // of date range
        $zeroTimeClock = factory(TimeClockLog::class)->create([
            'employee_id' => $tonyStark->id,
            'sub_cost_center_id' => $zeroScc->id,
            'checked_in_at' => $startDate->copy()->subDays(15)->setTime(07, 00),
            'checked_out_at' => $startDate->copy()->subDays(15)->setTime(18, 00),
            'expected_check_in_at' => $startDate->copy()->subDays(15)->setTime(07, 00),
            'expected_check_out_at' => $startDate->copy()->subDays(15)->setTime(18, 00),
        ]);

        // in range time clock logs
        $firstTimeClock = factory(TimeClockLog::class)->create([
            'employee_id' => $tonyStark->id,
            'sub_cost_center_id' => $firstScc->id,
            'checked_in_at' => $startDate->copy()->setTime(07, 00),
            'checked_out_at' => $startDate->copy()->setTime(18, 00),
            'expected_check_in_at' => $startDate->copy()->setTime(07, 00),
            'expected_check_out_at' => $startDate->copy()->setTime(18, 00),
        ]);

        // same day time clock logs
        $secondTimeClock = factory(TimeClockLog::class)->create([
            'employee_id' => $tonyStark->id,
            'sub_cost_center_id' => $secondScc->id,
            'checked_in_at' => $endDate->copy()->setTime(07, 00),
            'checked_out_at' => $endDate->copy()->setTime(12, 00),
            'expected_check_in_at' => $endDate->copy()->setTime(07, 00),
            'expected_check_out_at' => $endDate->copy()->setTime(12, 00),
        ]);

        $thirdTimeClock = factory(TimeClockLog::class)->create([
            'employee_id' => $tonyStark->id,
            'sub_cost_center_id' => $firstScc->id, // same day and employee, different sub cos center
            'checked_in_at' => $endDate->copy()->setTime(14, 00),
            'checked_out_at' => $endDate->copy()->setTime(18, 00),
            'expected_check_in_at' => $endDate->copy()->setTime(14, 00),
            'expected_check_out_at' => $endDate->copy()->setTime(18, 00),
        ]);

        // attach novelties to time clock logs
        $zeroTimeClockNovelties = factory(Novelty::class, 2)->create(['time_clock_log_id' => $zeroTimeClock->id]);
        $firstTimeClockNovelties = factory(Novelty::class, 4)->create(['time_clock_log_id' => $firstTimeClock->id]);
        $secondTimeClockNovelties = factory(Novelty::class, 6)->create(['time_clock_log_id' => $secondTimeClock->id]);
        $thirdTimeClockNovelties = factory(Novelty::class, 2)->create(['time_clock_log_id' => $thirdTimeClock->id, 'comment' => 'foo']);

        // attach same approvers to novelties second and third novelties
        $approvers = factory(User::class, 2)->create();
        $secondTimeClockNovelties->each(fn($novelty) => $novelty->approvals()->sync($approvers));
        $thirdTimeClockNovelties->each(fn($novelty) => $novelty->approvals()->sync($approvers));

        $action = app(GenerateReportByEmployee::class);
        $result = $action->run($tonyStark->id, $startDate, $endDate);

        // second and third time clock log was on same day and they are on the last day
        $I->assertEquals([
            'date' => $secondTimeClock->checked_in_at->toDateString(),
            'employee.identification_number' => $secondTimeClock->employee->identification_number,
            'sub_cost_centers' => [
                ['id' => $firstScc->id, 'code' => $firstScc->code, 'name' => $firstScc->name],
                ['id' => $secondScc->id, 'code' => $secondScc->code, 'name' => $secondScc->name],
            ],
            'novelties' => $secondTimeClockNovelties
                ->concat($thirdTimeClockNovelties)
                ->map(fn($novelty) => [
                    'id' => $novelty->id,
                    'novelty_type' => $novelty->noveltyType->name,
                    'total_time_in_minutes' => $novelty->total_time_in_minutes,
                ])->sortBy('novelty_type')->values()->all(),
            'novelties_time_sum' => $secondTimeClockNovelties->concat($thirdTimeClockNovelties)->sum('total_time_in_minutes'),
            'novelties_comments_count' => 2,
            'novelties_approvers' => $approvers->sortBy('first_name')->only(['id', 'first_name', 'last_name'])->values()->all(),
        ], $result[0]);
        $I->assertEquals(
            [ // this is the oldest time clock log
                'date' => $firstTimeClock->checked_in_at->toDateString(),
                'employee.identification_number' => $firstTimeClock->employee->identification_number,
                'sub_cost_centers' => [
                    ['id' => $firstScc->id, 'code' => $firstScc->code, 'name' => $firstScc->name],
                ],
                'novelties' => $firstTimeClockNovelties
                    ->map(fn($novelty) => [
                        'id' => $novelty->id,
                        'novelty_type' => $novelty->noveltyType->name,
                        'total_time_in_minutes' => $novelty->total_time_in_minutes,
                    ])->sortBy('novelty_type')->values()->all(),
                'novelties_time_sum' => $firstTimeClockNovelties->sum('total_time_in_minutes'),
                'novelties_comments_count' => 0,
                'novelties_approvers' => [],
            ], $result[1]);
    }
}
