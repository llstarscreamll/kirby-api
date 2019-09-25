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
        $currentDate = now();
        $timeClockLog = $this->timeClockLogRepository->with([
            'workShift', 'checkInNovelty', 'checkOutNovelty', 'novelties',
        ])->find($timeClockLogId);

        $this->attachScheduledNovelties($timeClockLog);

        $novelties = $this->getApplicableNovelties($timeClockLog)
            ->map(function ($noveltyType) use ($timeClockLog, $currentDate) {
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
                    'created_at' => $currentDate,
                    'updated_at' => $currentDate,
                ];
            })
            ->filter(function ($novelty) {
                return ! empty($novelty['total_time_in_minutes']);
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
            ->filter(function (Novelty $novelty) {
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

            // scheduled novelties should not exists if work shift is empty
            if (! $timeClockLog->hasWorkShift()) {
                return $this->scheduledNovelties = collect([]);
            }

            $start = $timeClockLog->workShift->minStartTimeSlot($timeClockLog->checked_in_at, $beGraceTimeAware);
            $end = $timeClockLog->workShift->maxEndTimeSlot($timeClockLog->checked_out_at, $beGraceTimeAware);

            $this->scheduledNovelties = $this->noveltyRepository
                ->whereScheduledForEmployee($timeClockLog->employee_id, 'start_at', $start, $end)
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

        if ($timeClockLog->hasWorkShift() && $timeClockLog->workShift->hasDeadTimes()) {
            $this->noveltyTypeRepository->orWhereDefaultForAddition();
        }

        $noveltyTypes = $noveltyTypeIds
            ? $this->noveltyTypeRepository->findOrWhereIn('id', $noveltyTypeIds)
            : $this->noveltyTypeRepository->get();

        $noveltyTypes = $noveltyTypes->filter(function (NoveltyType $noveltyType) use ($timeClockLog) {
            if (empty($noveltyType->apply_on_time_slots)) {
                return true;
            }

            $start = $noveltyType->minStartTimeSlot($timeClockLog->checked_in_at);
            $end = $noveltyType->maxEndTimeSlot($timeClockLog->checked_in_at);

            $relativeTo = $timeClockLog->checked_in_at;
            $beGraceTimeAware = false;
            $relativeToEnd = false;

            return $timeClockLog->hasWorkShift() && (
                $timeClockLog->workShift->isMinStartTimeSlotInRage($start, $end, $relativeTo) ||
                $timeClockLog->workShift->isMaxEndTimeSlotInRange($start, $end, $relativeTo, $beGraceTimeAware, $relativeToEnd)
            );
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
        $deadTimeInMinutes = 0;
        $subCostCenterId = $timeClockLog->sub_cost_center_id;
        $workShift = optional($timeClockLog->workShift);
        $checkInNoveltyTypeId = $timeClockLog->check_in_novelty_type_id;
        $checkOutNoveltyTypeId = $timeClockLog->check_out_novelty_type_id;
        [$startNoveltyMinutes, $clockedMinutes, $endNoveltyMinutes, $mealMinutes, $times] = $this->calculateTimeClockLogTimesInMinutes($timeClockLog);

        $checkedInAt = $timeClockLog->checked_in_at;
        $checkedOutAt = $timeClockLog->checked_out_at;
        $checkOutPunctuality = $timeClockLog->checkOutPunctuality();
        $checkInPunctuality = $timeClockLog->checkInPunctuality();
        $tooLateCheckIn = $checkInPunctuality === 1;
        $tooEarlyCheckOut = $checkOutPunctuality === -1;

        // solve dead time on work shift
        if ($timeClockLog->hasWorkShift()) {
            $deadTimeInMinutes = $workShift->deadTimeInMinutesFromTimeRange($checkedInAt, $checkedOutAt);
        }

        if ($timeClockLog->hasWorkShift() && $noveltyType->context_type === 'normal_work_shift_time' && $clockedMinutes[$noveltyType->apply_on_days_of_type->value]) {
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
                $timeInMinutes -= $deadTimeInMinutes;
            }

            $shouldDiscountMealTime = $timeClockLog->workShift->canMealTimeApply($timeInMinutes);

            $timeInMinutes -= $noveltyType->canApplyOnDayType(DayType::Holiday())
                ? $clockedMinutes[DayType::Workday]
                : $clockedMinutes[DayType::Holiday];

            if ($shouldDiscountMealTime) {
                $timeInMinutes -= $mealMinutes;
            }
        }

        $noveltyTimes = $noveltyType->apply_on_days_of_type
            ? $noveltyType->canApplyOnDayType(DayType::Holiday()) ? Arr::get($times, 'holidayTimes') : Arr::get($times, 'workdayTimes')
            : $this->getWiderTimes($times);

        $clockedMinutes = $noveltyType->apply_on_days_of_type
            ? $clockedMinutes[$noveltyType->apply_on_days_of_type->value]
            : array_sum($clockedMinutes);

        if ($checkInNoveltyTypeId === $noveltyType->id && $timeClockLog->hasWorkShift()) {
            $subCostCenterId = $timeClockLog->check_in_sub_cost_center_id ?? $subCostCenterId;
            $timeInMinutes += $startNoveltyMinutes;
        }

        if ($checkOutNoveltyTypeId === $noveltyType->id && $timeClockLog->hasWorkShift()) {
            $subCostCenterId = $timeClockLog->check_out_sub_cost_center_id ?? $subCostCenterId;
            $timeInMinutes += $endNoveltyMinutes;
        }

        if (! $checkInNoveltyTypeId && $tooLateCheckIn && $noveltyType->isDefaultForSubtraction()) {
            $timeInMinutes += $startNoveltyMinutes;
        }

        if (! $checkOutNoveltyTypeId && $tooEarlyCheckOut && $noveltyType->isDefaultForSubtraction()) {
            $timeInMinutes += $endNoveltyMinutes;
        }

        if (! $timeClockLog->hasWorkShift() && $checkInNoveltyTypeId) {
            $timeInMinutes = $clockedMinutes;
        }

        if ($noveltyType->isDefaultForAddition()) {
            $timeInMinutes += $deadTimeInMinutes;
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
            return $holidayTimes ?? $workdayTimes;
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
        $closestEndSlot = $workShift->getClosestSlotFlagTime('end', $timeClockLog->checked_out_at, $this->getTimeFlag('end', $timeClockLog));
        $closestStartSlot = $workShift->getClosestSlotFlagTime('start', $timeClockLog->checked_in_at, $this->getTimeFlag('start', $timeClockLog));

        // calculate check in novelty time
        if ($timeClockLog->hasWorkShift()) {
            $estimatedStartTime = $timeClockLog->checked_out_at->lessThan($closestStartSlot)
                ? $timeClockLog->checked_out_at : $closestStartSlot;

            $startNoveltyMinutes = $timeClockLog->checkInPunctuality() !== 0 ? $estimatedStartTime->diffInMinutes($timeClockLog->checked_in_at) : 0;
            $times['startNoveltyTimes'] = ['start' => $estimatedStartTime, 'end' => $timeClockLog];

            if (! $timeClockLog->checkInNovelty || $timeClockLog->checkInNovelty->operator->is(NoveltyTypeOperator::Subtraction)) {
                $startNoveltyMinutes *= -1;
            }
        }

        // calculate check out novelty time
        if ($timeClockLog->hasWorkShift()) {
            $estimatedEndTime = $timeClockLog->checked_out_at->lessThan($closestStartSlot)
                ? $closestStartSlot : $timeClockLog->checked_out_at;

            $endNoveltyMinutes = $closestEndSlot->diffInMinutes($estimatedEndTime);
            $times['endNoveltyMinutes'] = ['start' => $closestEndSlot, 'end' => $estimatedEndTime];

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
    private function getTimeFlag(string $flag, TimeClockLog $timeClockLog): ?Carbon
    {
        $logAction = $flag === 'start' ? 'checked_in_at' : 'checked_out_at';
        $comparison = $flag === 'start' ? 'lessThan' : 'greaterThan';
        $comparisonFlag = $flag === 'start' ? 'end_at' : 'start_at';
        $scheduledNovelties = $this->scheduledNovelties($timeClockLog)
            ->filter(function (Novelty $novelty) use ($timeClockLog) {
                return ! $novelty->time_clock_log_id || $novelty->timeClockLog->checked_in_at->between(
                    $timeClockLog->checked_in_at, $timeClockLog->checked_out_at,
                );
            });

        if (! $scheduledNovelties->count()) {
            return null;
        }

        $closestScheduledNovelty = $scheduledNovelties
            ->filter(function (Novelty $novelty) use ($timeClockLog, $comparisonFlag, $comparison, $logAction) {
                return $novelty->{$comparisonFlag}->{$comparison}($timeClockLog->{$logAction});
            })
            ->sortBy(function (Novelty $novelty) use ($timeClockLog, $comparisonFlag, $logAction) {
                return $novelty->{$comparisonFlag}->diffInMinutes($timeClockLog->{$logAction});
            })->first();

        return optional($closestScheduledNovelty)->{$comparisonFlag} ?? $timeClockLog->{$logAction};
    }
}
