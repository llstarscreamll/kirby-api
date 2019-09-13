<?php

namespace llstarscreamll\Novelties\Actions;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use llstarscreamll\Novelties\Enums\DayType;
use llstarscreamll\Novelties\Models\Novelty;
use llstarscreamll\Novelties\Models\NoveltyType;
use llstarscreamll\TimeClock\Models\TimeClockLog;
use llstarscreamll\Novelties\Enums\NoveltyTypeOperator;
use llstarscreamll\Company\Contracts\HolidayRepositoryInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use llstarscreamll\Novelties\Contracts\NoveltyRepositoryInterface;
use llstarscreamll\Novelties\Contracts\NoveltyTypeRepositoryInterface;
use llstarscreamll\TimeClock\Contracts\TimeClockLogRepositoryInterface;

/**
 * Class RegisterTimeClockNoveltiesAction.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class RegisterTimeClockNoveltiesAction
{
    /**
     * @var HolidayRepositoryInterface
     */
    private $holidayRepository;

    /**
     * @var NoveltyRepositoryInterface
     */
    private $noveltyRepository;

    /**
     * @var NoveltyTypeRepository
     */
    private $noveltyTypeRepository;

    /**
     * @var TimeClockLogRepositoryInterface
     */
    private $timeClockLogRepository;

    /**
     * @var Collection
     */
    private $scheduledNovelties;

    /**
     * @param HolidayRepositoryInterface      $holidayRepository
     * @param NoveltyRepositoryInterface      $noveltyRepository
     * @param NoveltyTypeRepositoryInterface  $noveltyTypeRepository
     * @param TimeClockLogRepositoryInterface $timeClockLogRepository
     */
    public function __construct(
        HolidayRepositoryInterface $holidayRepository,
        NoveltyRepositoryInterface $noveltyRepository,
        NoveltyTypeRepositoryInterface $noveltyTypeRepository,
        TimeClockLogRepositoryInterface $timeClockLogRepository
    ) {
        $this->holidayRepository = $holidayRepository;
        $this->noveltyRepository = $noveltyRepository;
        $this->noveltyTypeRepository = $noveltyTypeRepository;
        $this->timeClockLogRepository = $timeClockLogRepository;
    }

    /**
     * @param  int    $timeClockLogId
     * @return bool
     */
    public function run(int $timeClockLogId): bool
    {
        $date = now();
        $timeClockLog = $this->timeClockLogRepository->with([
            'workShift', 'checkInNovelty', 'checkOutNovelty', 'novelties',
        ])->find($timeClockLogId);
        $this->attachScheduledNovelties($timeClockLog);
        $applicableNovelties = $this->getApplicableNovelties($timeClockLog);

        $novelties = $applicableNovelties
            ->map(function ($noveltyType) use ($timeClockLog, $date) {
                [$minutes, $subCostCenterId, $times] = $this->solveTimeForNoveltyType($timeClockLog, $noveltyType);
                $startAt = Arr::first($times);
                $endAt = Arr::last($times);

                return [
                    'time_clock_log_id' => $timeClockLog->id,
                    'employee_id' => $timeClockLog->employee_id,
                    'novelty_type_id' => $noveltyType->id,
                    'sub_cost_center_id' => $subCostCenterId,
                    'total_time_in_minutes' => $minutes,
                    'start_at' => optional($startAt)->toDateTimeString(),
                    'end_at' => optional($endAt)->toDateTimeString(),
                    'created_at' => $date,
                    'updated_at' => $date,
                ];
            })
            ->filter(function ($novelty) {
                return $novelty['total_time_in_minutes'] !== 0 && $novelty['total_time_in_minutes'] !== 0.0;
            });

        $this->noveltyRepository->insert($novelties->all());

        return true;
    }

    /**
     * @param  TimeClockLog $timeClockLog
     * @return int
     */
    private function attachScheduledNovelties(TimeClockLog $timeClockLog): int
    {
        $scheduledNoveltiesIds = $this->scheduledNovelties($timeClockLog)
            ->filter(function ($novelty) {
                return empty($novelty->time_clock_log_id);
            })
            ->pluck('id')
            ->all();

        return $this->noveltyRepository->updateWhereIn('id', $scheduledNoveltiesIds, [
            'time_clock_log_id' => $timeClockLog->id,
            'sub_cost_center_id' => $timeClockLog->sub_cost_center_id,
        ]);
    }

    /**
     * @param  TimeClockLog $timeClockLog
     * @return Collection
     */
    private function scheduledNovelties(TimeClockLog $timeClockLog): Collection
    {
        if (! $this->scheduledNovelties) {
            $beGraceTimeAware = true;
            $employeeId = $timeClockLog->employee->id;

            // scheduled novelties should not exists if work shift is empty
            if (! $timeClockLog->workShift) {
                return $this->scheduledNovelties = collect([]);
            }

            $start = $timeClockLog->workShift->minStartTimeSlot($timeClockLog->checked_in_at, $beGraceTimeAware);
            $end = $timeClockLog->workShift->maxEndTimeSlot($timeClockLog->checked_out_at, $beGraceTimeAware);

            $this->scheduledNovelties = $this->noveltyRepository
                ->whereScheduledForEmployee($employeeId, 'start_at', $start, $end)
                ->get();
        }

        return $this->scheduledNovelties;
    }

    /**
     * Get the applicable novelty types to $timeClockLog.
     *
     * @param  TimeClockLog         $timeClockLog
     * @return EloquentCollection
     */
    private function getApplicableNovelties(TimeClockLog $timeClockLog): EloquentCollection
    {
        $noveltyTypeIds = array_filter([
            $timeClockLog->check_in_novelty_type_id,
            $timeClockLog->check_out_novelty_type_id,
        ]);

        $dayTypes = [DayType::Workday];
        $timeClockLog->hasHolidaysChecks() ? array_push($dayTypes, DayType::Holiday) : null;
        $this->noveltyTypeRepository->whereDayType($dayTypes);

        if ($timeClockLog->checkInPunctuality() === 1 || $timeClockLog->checkOutPunctuality() === -1) {
            $this->noveltyTypeRepository->orWhereDefaultForSubtraction();
        }

        $noveltyTypes = $noveltyTypeIds
            ? $this->noveltyTypeRepository->findOrWhereIn('id', $noveltyTypeIds)
            : $this->noveltyTypeRepository->get();

        $noveltyTypes = $noveltyTypes->filter(function (NoveltyType $noveltyType) use ($timeClockLog) {
            // filter by time slots
            return collect($noveltyType->apply_on_time_slots)
                ->filter(function (?array $timeSlot) use ($timeClockLog, $noveltyType) {

                    return $timeClockLog->workShift
                        && (optional($timeClockLog->workShift->minStartTimeSlot($timeClockLog->checked_in_at))->between($noveltyType->minStartTimeSlot($timeClockLog->checked_in_at), $noveltyType->maxEndTimeSlot($timeClockLog->checked_in_at))
                        || optional($timeClockLog->workShift->maxEndTimeSlot($timeClockLog->checked_in_at, false, false))->between($noveltyType->minStartTimeSlot($timeClockLog->checked_in_at), $noveltyType->maxEndTimeSlot($timeClockLog->checked_in_at)));
                })->count() > 0 || empty($noveltyType->apply_on_time_slots);
        });

        return $noveltyTypes;
    }

    /**
     * @param  TimeClockLog $timeClockLog
     * @param  NoveltyType  $noveltyType
     * @return array
     */
    private function solveTimeForNoveltyType(TimeClockLog $timeClockLog, NoveltyType $noveltyType): array
    {
        $timeInMinutes = 0;
        $noveltyTimes = [];
        $subCostCenterId = $timeClockLog->sub_cost_center_id;
        $workShift = optional($timeClockLog->workShift);
        $clockedMinutes = $timeClockLog->clocked_minutes;
        $checkInNoveltyTypeId = $timeClockLog->check_in_novelty_type_id;
        $checkOutNoveltyTypeId = $timeClockLog->check_out_novelty_type_id;
        [$startNoveltyMinutes, $clockedMinutes, $endNoveltyMinutes, $mealMinutes, $times] = $this->calculateTimeClockLogTimesInMinutes($timeClockLog);

        $checkedInAt = $timeClockLog->checked_in_at;
        $checkedOutAt = $timeClockLog->checked_out_at;
        $checkOutPunctuality = $timeClockLog->checkOutPunctuality();
        $checkInPunctuality = $timeClockLog->checkInPunctuality();

        if ($timeClockLog->work_shift_id && $noveltyType->context_type === 'normal_work_shift_time' && $clockedMinutes[$noveltyType->apply_on_days_of_type->value]) {
            if ($workShift && $timeClockLog->hasClockedTimeOnWorkShift()) {
                // on time or early
                $startTime = in_array($checkInPunctuality, [-1, 0])
                    ? $workShift->getClosestSlotFlagTime('start', $checkedInAt)
                    : $checkedInAt;

                // on time or late
                $endTime = in_array($checkOutPunctuality, [0, 1])
                    ? $workShift->getClosestSlotFlagTime('end', $checkedOutAt)
                    : $checkedOutAt;

                $timeInMinutes = $noveltyType->applicableTimeInMinutesFromTimeRange($startTime, $endTime);
            }

            $shouldDiscountMealTime = optional($timeClockLog->workShift)->min_minutes_required_to_discount_meal_time && $timeInMinutes >= optional($timeClockLog->workShift)->min_minutes_required_to_discount_meal_time;

            $timeInMinutes -= $noveltyType->apply_on_days_of_type->is(DayType::Holiday)
                ? $clockedMinutes[DayType::Workday]
                : $clockedMinutes[DayType::Holiday];

            $noveltyTimes = $noveltyType->apply_on_days_of_type->is(DayType::Holiday)
                ? Arr::get($times, 'workdayTimes')
                : Arr::get($times, 'holidayTimes');

            if ($shouldDiscountMealTime) {
                $timeInMinutes -= $mealMinutes;
            }
        }

        $noveltyTimes = $noveltyType->apply_on_days_of_type
            ? optional($noveltyType->apply_on_days_of_type)->is(DayType::Holiday) ? Arr::get($times, 'holidayTimes') : Arr::get($times, 'workdayTimes')
            : $this->getWiderTimes($times);

        $clockedMinutes = $noveltyType->apply_on_days_of_type
            ? $clockedMinutes[$noveltyType->apply_on_days_of_type->value]
            : array_sum($clockedMinutes);

        if ($checkInNoveltyTypeId === $noveltyType->id && $timeClockLog->work_shift_id) {
            $subCostCenterId = $timeClockLog->check_in_sub_cost_center_id ?? $subCostCenterId;
            $timeInMinutes += $startNoveltyMinutes;
        }

        if ($checkOutNoveltyTypeId === $noveltyType->id && $timeClockLog->work_shift_id) {
            $subCostCenterId = $timeClockLog->check_out_sub_cost_center_id ?? $subCostCenterId;
            $timeInMinutes += $endNoveltyMinutes;
        }

        if (! $checkInNoveltyTypeId && $checkInPunctuality === 1 && $noveltyType->code == 'PP') {
            $timeInMinutes += $startNoveltyMinutes;
        }

        if (! $checkOutNoveltyTypeId && $checkOutPunctuality === -1 && $noveltyType->code == 'PP') {
            $timeInMinutes += $endNoveltyMinutes;
        }

        if (! $timeClockLog->work_shift_id && $checkInNoveltyTypeId) {
            $timeInMinutes = $clockedMinutes;
        }

        return [$timeInMinutes, $subCostCenterId, $noveltyTimes];
    }

    /**
     * @param array $times
     */
    private function getWiderTimes(array $times): array
    {
        $holidayTimes = $times['holidayTimes'];
        $workdayTimes = $times['workdayTimes'];

        if (empty($holidayTimes) || empty($workdayTimes)) {
            return empty($holidayTimes) ? $workdayTimes : $holidayTimes;
        }

        [$holidayStartTime, $holidayEndTime] = $holidayTimes;
        [$workdayStartTime, $workdayEndTime] = $workdayTimes;

        return [
            $holidayStartTime->lessThan($workdayStartTime) ? $holidayStartTime : $workdayStartTime,
            $holidayEndTime->greaterThan($workdayEndTime) ? $holidayEndTime : $workdayEndTime,
        ];
    }

    /**
     * Calculate time clock times: start novelty type, work time and end novelty
     * type.
     *
     * @param  TimeClockLog $timeClockLog
     * @return array
     */
    private function calculateTimeClockLogTimesInMinutes(TimeClockLog $timeClockLog): array
    {
        $times = [];
        $workMinutes = [
            DayType::Holiday => 0,
            DayType::Workday => 0,
        ];
        $startNoveltyMinutes = 0;
        $endNoveltyMinutes = 0;
        $workShift = optional($timeClockLog->workShift);
        $mealMinutes = $workShift->meal_time_in_minutes ?? 0;
        $closestEndSlot = $workShift->getClosestSlotFlagTime('end', $timeClockLog->checked_out_at, $this->getEndTime($timeClockLog));
        $closestStartSlot = $workShift->getClosestSlotFlagTime('start', $timeClockLog->checked_in_at, $this->getStartTime($timeClockLog));

        // calculate check in novelty time
        if ($timeClockLog->work_shift_id) {
            $estimatedStartTime = $timeClockLog->checked_out_at->lessThan($closestStartSlot)
                ? $timeClockLog->checked_out_at : $closestStartSlot;

            $startNoveltyMinutes = $timeClockLog->checkInPunctuality() !== 0 ? $estimatedStartTime->diffInMinutes($timeClockLog->checked_in_at) : 0;
            $times['startNoveltyTimes'] = ['start' => $estimatedStartTime, 'end' => $timeClockLog];

            if (! $timeClockLog->checkInNovelty || $timeClockLog->checkInNovelty->operator->is(NoveltyTypeOperator::Subtraction)) {
                $startNoveltyMinutes *= -1;
            }
        }

        // calculate check out novelty time
        if ($timeClockLog->work_shift_id) {
            $endTime = $timeClockLog->checked_out_at->lessThan($closestStartSlot)
                ? $closestStartSlot : $timeClockLog->checked_out_at;

            $endNoveltyMinutes = $closestEndSlot->diffInMinutes($endTime);
            $times['endNoveltyMinutes'] = ['start' => $closestEndSlot, 'end' => $endTime];

            if (! $timeClockLog->checkOutNovelty || $timeClockLog->checkOutNovelty->operator->is(NoveltyTypeOperator::Subtraction)) {
                $endNoveltyMinutes *= -1;
            }
        }

        [$holidayTimeInMinutes, $holidayTimes] = $timeClockLog->getClockedTimeMinutesByDayType(DayType::Holiday());
        $workMinutes[DayType::Holiday] += $holidayTimeInMinutes;
        $times['holidayTimes'] = $holidayTimes;
        [$workdayTimeInMinutes, $workdayTimes] = $timeClockLog->getClockedTimeMinutesByDayType(DayType::Workday());
        $workMinutes[DayType::Workday] += $workdayTimeInMinutes;
        $times['workdayTimes'] = $workdayTimes;

        return [
            $startNoveltyMinutes,
            $workMinutes,
            $endNoveltyMinutes,
            $mealMinutes,
            $times,
        ];
    }

    /**
     * @param  TimeClockLog  $timeClockLog
     * @return null|Carbon
     */
    private function getStartTime(TimeClockLog $timeClockLog): ?Carbon
    {
        $scheduledNovelties = $this->scheduledNovelties($timeClockLog);

        if (! $scheduledNovelties->count()) {
            return null;
        }

        $closestScheduledNovelty = $scheduledNovelties
            ->filter(function (Novelty $novelty) use ($timeClockLog) {
                return $novelty->end_at->lessThan($timeClockLog->checked_in_at);
            })
            ->sortBy(function (Novelty $novelty) use ($timeClockLog) {
                return $novelty->end_at->diffInMinutes($timeClockLog->checked_in_at);
            })->first();

        return optional($closestScheduledNovelty)->end_at ?? $timeClockLog->checked_in_at;
    }

    /**
     * @param  TimeClockLog  $timeClockLog
     * @return null|Carbon
     */
    private function getEndTime(TimeClockLog $timeClockLog): ?Carbon
    {
        $scheduledNovelties = $this->scheduledNovelties($timeClockLog);

        if (! $scheduledNovelties->count()) {
            return null;
        }

        $closestScheduledNovelty = $scheduledNovelties
            ->filter(function (Novelty $novelty) use ($timeClockLog) {
                return $novelty->start_at->greaterThan($timeClockLog->checked_out_at);
            })
            ->sortBy(function (Novelty $novelty) use ($timeClockLog) {
                return $novelty->start_at->diffInMinutes($timeClockLog->checked_out_at);
            })->first();

        return optional($closestScheduledNovelty)->start_at ?? $timeClockLog->checked_out_at;
    }
}
