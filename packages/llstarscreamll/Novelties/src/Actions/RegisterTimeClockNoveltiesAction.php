<?php

namespace llstarscreamll\Novelties\Actions;

use llstarscreamll\Novelties\Enums\DayType;
use llstarscreamll\Novelties\Models\NoveltyType;
use llstarscreamll\TimeClock\Models\TimeClockLog;
use llstarscreamll\Novelties\Enums\NoveltyTypeOperator;
use llstarscreamll\Company\Contracts\HolidayRepositoryInterface;
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
     * @param int $timeClockLogId
     */
    public function run(int $timeClockLogId)
    {
        $timeClockLog = $this->timeClockLogRepository->with([
            'workShift', 'checkInNovelty', 'checkOutNovelty', 'novelties',
        ])->find($timeClockLogId);
        $aplicableNovelties = $this->getApplicableNovelties($timeClockLog);

        $date = now();

        $novelties = $aplicableNovelties
            ->map(function ($noveltyType) use ($timeClockLog, $date) {
                return [
                    'time_clock_log_id' => $timeClockLog->id,
                    'employee_id' => $timeClockLog->employee_id,
                    'novelty_type_id' => $noveltyType->id,
                    'total_time_in_minutes' => $this->solveTimeForNoveltyType($timeClockLog, $noveltyType), // in minutes
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
     * Get the applicable novelty types to $timeClockLog.
     *
     * @param TimeClockLog $timeClockLog
     */
    private function getApplicableNovelties(TimeClockLog $timeClockLog)
    {
        $noveltyTypeIds = array_filter([
            $timeClockLog->check_in_novelty_type_id,
            $timeClockLog->check_out_novelty_type_id,
        ]);

        $dayTypes = [DayType::Workday];
        $timeClockLog->hasHolidaysChecks() ? array_push($dayTypes, DayType::Holiday) : null;
        $this->noveltyTypeRepository->whereDayType($dayTypes);

        $noveltyTypes = $noveltyTypeIds
            ? $this->noveltyTypeRepository->findOrWhereIn('id', $noveltyTypeIds)
            : $this->noveltyTypeRepository->get();

        $noveltyTypes = $noveltyTypes->filter(function (NoveltyType $noveltyType) use ($timeClockLog) {
            // filter by time slots
            return collect($noveltyType->apply_on_time_slots)

                ->filter(function (?array $timeSlot) use ($timeClockLog, $noveltyType) {
                    [$hours, $seconds] = explode(':', $timeSlot['start']);
                    $start = now()->setTime($hours, $seconds);
                    [$hours, $seconds] = explode(':', $timeSlot['end']);
                    $end = now()->setTime($hours, $seconds);

                    return $timeClockLog->workShift
                        && (optional($timeClockLog->workShift->minStartTimeSlot())->between($noveltyType->minStartTimeSlot(), $noveltyType->maxEndTimeSlot())
                        || optional($timeClockLog->workShift->maxEndTimeSlot())->between($noveltyType->minStartTimeSlot(), $noveltyType->maxEndTimeSlot()));
                })->count() > 0 || empty($noveltyType->apply_on_time_slots);
        });

        return $noveltyTypes;
    }

    /**
     * @param  TimeClockLog $timeClockLog
     * @param  NoveltyType  $noveltyType
     * @return mixed
     */
    private function solveTimeForNoveltyType(TimeClockLog $timeClockLog, NoveltyType $noveltyType)
    {
        $timeInMinutes = 0;
        $workShift = $timeClockLog->workShift;
        $checkInNoveltyTypeId = $timeClockLog->check_in_novelty_type_id;
        $checkOutNoveltyTypeId = $timeClockLog->check_out_novelty_type_id;
        $clockedMinutes = $timeClockLog->clocked_minutes;
        $shouldDiscountMealTime = $clockedMinutes >= optional($timeClockLog->workShift)->min_minutes_required_to_discount_meal_time;
        [$startNoveltyMinutes, $clockedMinutes, $endNoveltyMinutes, $mealMinutes] = $this->calculateTimeClockLogTimesInMinutes($timeClockLog);

        if ($timeClockLog->work_shift_id && $noveltyType->context_type === 'normal_work_shift_time' && $clockedMinutes[$noveltyType->apply_on_days_of_type->value]) {
            if ($workShift) {
                $checkedInAt = $timeClockLog->checked_in_at;
                $checkedOutAt = $timeClockLog->checked_out_at;

                $startTime = $checkedInAt->between($workShift->minStartTimeSlot($checkedInAt), $workShift->maxEndTimeSlot($checkedInAt))
                    ? $checkedInAt : $workShift->minStartTimeSlot($checkedInAt);

                $endTime = $checkedOutAt->between($workShift->minStartTimeSlot($checkedOutAt), $workShift->maxEndTimeSlot($checkedInAt))
                    ? $checkedOutAt : $workShift->maxEndTimeSlot($checkedInAt);

                $workShiftApplicableTime = $noveltyType->applicableTimeInMinutesFromTimeRange($startTime, $endTime);
                $timeInMinutes = $workShiftApplicableTime;
            }

            $timeInMinutes -= $noveltyType->apply_on_days_of_type->is(DayType::Holiday)
                ? $clockedMinutes[DayType::Workday]
                : $clockedMinutes[DayType::Holiday];

            if ($shouldDiscountMealTime) {
                $timeInMinutes -= $mealMinutes;
            }
        }

        $clockedMinutes = $noveltyType->apply_on_days_of_type
            ? $clockedMinutes[$noveltyType->apply_on_days_of_type->value]
            : array_sum($clockedMinutes);

        if ($checkInNoveltyTypeId === $noveltyType->id && $timeClockLog->work_shift_id) {
            $timeInMinutes += $startNoveltyMinutes;
        }

        if ($checkOutNoveltyTypeId === $noveltyType->id && $timeClockLog->work_shift_id) {
            $timeInMinutes += $endNoveltyMinutes;
        }

        if (!$timeClockLog->work_shift_id && $checkInNoveltyTypeId) {
            $timeInMinutes = $clockedMinutes;
        }

        return $timeInMinutes;
    }

    /**
     * Calculate time clock times: start novelty type, work time and end novelty
     * type.
     *
     * @param  $timeClockLog
     * @return array
     */
    private function calculateTimeClockLogTimesInMinutes(TimeClockLog $timeClockLog): array
    {
        $workMinutes = [
            DayType::Holiday => 0,
            DayType::Workday => 0,
        ];
        $startNoveltyMinutes = 0;
        $endNoveltyMinutes = 0;
        $workShift = optional($timeClockLog->workShift);
        $clockedMinutes = $timeClockLog->clocked_minutes;
        $mealMinutes = $workShift->meal_time_in_minutes ?? 0;
        $closestEndSlot = $workShift->getClosestSlotFlagTime('end', $timeClockLog->checked_out_at);
        $closestStartSlot = $workShift->getClosestSlotFlagTime('start', $timeClockLog->checked_in_at);
        $shouldDiscountMealTime = $clockedMinutes >= $workShift->min_minutes_required_to_discount_meal_time;

        // calculate check in novelty time
        if ($timeClockLog->check_in_novelty_type_id && $timeClockLog->work_shift_id) {
            $startNoveltyMinutes = $closestStartSlot->diffInMinutes($timeClockLog->checked_in_at);

            if ($timeClockLog->checkInNovelty->operator->is(NoveltyTypeOperator::Subtraction)) {
                $startNoveltyMinutes *= -1;
            }
        }

        // calculate check out novelty time
        if ($timeClockLog->check_out_novelty_type_id && $timeClockLog->work_shift_id) {
            $endNoveltyMinutes = $closestEndSlot->diffInMinutes($timeClockLog->checked_out_at);

            if ($timeClockLog->checkOutNovelty->operator->is(NoveltyTypeOperator::Subtraction)) {
                $endNoveltyMinutes *= -1;
            }
        }

        $workMinutes[DayType::Workday] += $timeClockLog->getClockedTimeMinutesByDayType(DayType::Workday());
        $workMinutes[DayType::Holiday] += $timeClockLog->getClockedTimeMinutesByDayType(DayType::Holiday());

        return [
            $startNoveltyMinutes,
            $workMinutes,
            $endNoveltyMinutes,
            $mealMinutes,
        ];
    }
}
