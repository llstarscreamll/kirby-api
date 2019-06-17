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
        $timeClockLog = $this->timeClockLogRepository->find($timeClockLogId);
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
                return $novelty['total_time_in_minutes'] !== 0;
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
        $holidaysCount = $this->holidayRepository->countWhereIn('date', [
            $timeClockLog->checked_in_at,
            $timeClockLog->checked_out_at,
        ]);

        $timeClockLog->work_shift_id
            ? $this->noveltyTypeRepository->whereContextType('logging_work_shift_time')
            : $this->noveltyTypeRepository->whereApplicableOnAnyDayType();

        $noveltyTypeIds = array_filter([
            $timeClockLog->check_in_novelty_type_id,
            $timeClockLog->check_out_novelty_type_id,
        ]);

        $dayType = $holidaysCount ? DayType::Holiday : DayType::Workday;
        $this->noveltyTypeRepository->whereDayType($dayType);

        $noveltyTypes = $noveltyTypeIds
            ? $this->noveltyTypeRepository->findOrWhereIn('id', $noveltyTypeIds)
            : $this->noveltyTypeRepository->get();

        $noveltyTypes = $noveltyTypes->filter(function (NoveltyType $novelty) use ($timeClockLog) {
            // filter by time slots
            return collect($novelty->apply_on_time_slots)
                ->filter(function (?array $timeSlot) use ($timeClockLog) {
                    // check in
                    [$hours, $seconds] = explode(':', $timeSlot['start']);
                    $noveltySlotStartCheckIn = $timeClockLog->checked_in_at->copy()->setTime($hours, $seconds);
                    $noveltySlotStartCheckOut = $timeClockLog->checked_out_at->copy()->setTime($hours, $seconds);
                    // check out
                    [$hours, $seconds] = explode(':', $timeSlot['end']);
                    $noveltySlotEndCheckIn = $timeClockLog->checked_in_at->copy()->setTime($hours, $seconds);
                    $noveltySlotEndCheckOut = $timeClockLog->checked_out_at->copy()->setTime($hours, $seconds);

                    return $timeClockLog->checked_in_at->between($noveltySlotStartCheckIn, $noveltySlotEndCheckIn)
                    || $timeClockLog->checked_out_at->between($noveltySlotStartCheckOut, $noveltySlotEndCheckOut);
                })->count() > 0 || empty($novelty->apply_on_time_slots);
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
        $checkInNoveltyTypeId = $timeClockLog->check_in_novelty_type_id;
        $checkOutNoveltyTypeId = $timeClockLog->check_out_novelty_type_id;
        [$startNoveltyMinutes, $workMinutes, $endNoveltyMinutes] = $this->calculateTimeClockLogTimesInMinutes($timeClockLog);

        if ($noveltyType->context_type === 'logging_work_shift_time' && $timeClockLog->work_shift_id) {
            // subtract start/end novelty time if has negative value
            $timeInMinutes = $startNoveltyMinutes < 0 ? $workMinutes + $startNoveltyMinutes : $workMinutes;
            $timeInMinutes = $endNoveltyMinutes < 0 ? $timeInMinutes + $endNoveltyMinutes : $timeInMinutes;
        }

        if ($checkInNoveltyTypeId === $noveltyType->id && $timeClockLog->work_shift_id) {
            $timeInMinutes += $startNoveltyMinutes;
        }

        if ($checkOutNoveltyTypeId === $noveltyType->id && $timeClockLog->work_shift_id) {
            $timeInMinutes += $endNoveltyMinutes;
        }

        if (!$timeClockLog->work_shift_id && $checkInNoveltyTypeId) {
            $timeInMinutes = $workMinutes;
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
        $workMinutes = 0;
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

        $workMinutes = ($timeClockLog->clocked_minutes) - ($startNoveltyMinutes + $endNoveltyMinutes);

        if ($shouldDiscountMealTime) {
            $workMinutes -= $mealMinutes;
        }

        return [
            $startNoveltyMinutes,
            $workMinutes,
            $endNoveltyMinutes,
        ];
    }
}
