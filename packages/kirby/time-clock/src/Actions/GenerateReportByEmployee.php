<?php

namespace Kirby\TimeClock\Actions;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Kirby\TimeClock\Contracts\TimeClockLogRepositoryInterface;

/**
 * Class GenerateReportByEmployee.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class GenerateReportByEmployee
{
    /**
     * @var TimeClockLogRepositoryInterface
     */
    private $timeClockLogRepository;

    /**
     * @param TimeClockLogRepositoryInterface $timeClockLogRepository
     */
    public function __construct(TimeClockLogRepositoryInterface $timeClockLogRepository)
    {
        $this->timeClockLogRepository = $timeClockLogRepository;
    }

    /**
     * @param int    $employeeId
     * @param Carbon $startDate
     * @param Carbon $endDate
     */
    public function run(int $employeeId, Carbon $startDate, Carbon $endDate)
    {
        $logs = $this->timeClockLogRepository
            ->with(['employee', 'novelties.noveltyType'])
            ->findByEmployeeId($employeeId)
            ->findWhereBetween('checked_in_at', [
                $startDate->startOfDay()->toDateTimeString(),
                $endDate->endOfDay()->toDateTimeString(),
            ]);

        return $logs->groupBy(fn ($log) => $log->checked_in_at->toDateString())
            ->map
            ->reduce(function ($accumulator, $log) {
                $subCostCenters = Arr::get($accumulator, 'sub_cost_centers', new Collection([]));
                $novelties = Arr::get($accumulator, 'novelties', new Collection([]));

                return [
                    'date' => $log->checked_in_at->toDateString(),
                    'employee.identification_number' => $log->employee->identification_number,
                    'sub_cost_centers' => $subCostCenters->concat($log->allSubCostCenters()),
                    'novelties' => $novelties->concat($log->novelties),
                ];
            }, [])
            ->map(function ($reportRow) {
                $reportRow['novelties_time_sum'] = $reportRow['novelties']->sum('total_time_in_minutes');
                $reportRow['novelties_approvers'] = $reportRow['novelties']->map
                    ->approvers
                    ->collapse()
                    ->unique('id')
                    ->sortBy('first_name')
                    ->only(['id', 'first_name', 'last_name'])
                    ->all();

                $reportRow['novelties_comments_count'] = $reportRow['novelties']
                    ->reject(fn ($novelty) => empty($novelty->comment))
                    ->count();

                $reportRow['novelties'] = $reportRow['novelties']
                    ->map(fn ($novelty) => [
                        'id' => $novelty->id,
                        'novelty_type' => $novelty->noveltyType->name,
                        'total_time_in_minutes' => $novelty->total_time_in_minutes,
                    ])->sortBy('novelty_type')->values()->all();

                $reportRow['sub_cost_centers'] = $reportRow['sub_cost_centers']
                    ->map->only(['id', 'code', 'name'])->sortBy('name')->values()->all();

                return $reportRow;
            })->sortByDesc('date')->values()->all();
    }
}
