<?php

namespace Kirby\TimeClock\Traits;

use Illuminate\Support\Collection;
use Kirby\Employees\Models\Identification;
use Kirby\Novelties\Enums\DayType;
use Kirby\Novelties\Enums\NoveltyTypeOperator;
use Kirby\Novelties\Models\NoveltyType;
use Kirby\WorkShifts\Models\WorkShift;

/**
 * Trait CheckInOut.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
trait CheckInOut
{
    /**
     * @param int $workShiftId
     */
    protected function getApplicableWorkShifts(Identification $identification, ?int $workShiftId): Collection
    {
        $deductedWorkShifts = $identification
            ->employee
            ->getWorkShiftsThatMatchesTime(now());

        if ($workShiftId) {
            $deductedWorkShifts = $identification
                ->employee
                ->workShifts
                ->where('id', $workShiftId);
        }

        return $deductedWorkShifts->values();
    }

    /**
     * @param WorkShift $workShift
     *
     * @return mixed
     */
    protected function noveltyIsValid(string $flag, ?WorkShift $workShift, ?NoveltyType $noveltyType = null): bool
    {
        $isValid = true;
        $shiftPunctuality = optional($workShift)->slotPunctuality($flag, now());

        $lateNoveltyOperator = 'start' === $flag ? NoveltyTypeOperator::Subtraction : NoveltyTypeOperator::Addition;
        $eagerNoveltyOperator = 'start' === $flag ? NoveltyTypeOperator::Addition : NoveltyTypeOperator::Subtraction;

        if ($workShift && $shiftPunctuality > 0 && $noveltyType && !$noveltyType->operator->is($lateNoveltyOperator)) {
            $isValid = false;
        }

        if ($workShift && $shiftPunctuality < 0 && $noveltyType && !$noveltyType->operator->is($eagerNoveltyOperator)) {
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * @param int $workShiftId
     */
    protected function getTimeClockData(string $flag, Identification $identification, ?int $workShiftId = null): array
    {
        $currentDateTime = now();
        $targetFlag = 'start' === $flag ? 'end' : 'start';
        $noveltyAttr = "{$targetFlag}_at";

        $scheduledNovelty = $this->noveltyRepository
            ->whereScheduledForEmployee($identification->employee_id, $noveltyAttr, now()->subHour(), now()->endOfDay())
            ->orderBy('id', 'DESC')
            ->first(['novelties.*']);

        $checkOffset = optional($scheduledNovelty)->{$noveltyAttr};

        $applicableWorkShifts = $this->getApplicableWorkShifts($identification, $workShiftId);
        $workShift = $applicableWorkShifts->first();
        $punctuality = 1 === $applicableWorkShifts->count() ? optional($workShift)->slotPunctuality($flag, $currentDateTime, $checkOffset) : null;

        $isOnTime = 0 === $punctuality;
        $noveltyTypes = new Collection([]);
        $noveltyIsRequired = $this->noveltyTypeIsRequiredForNonPunctualChecks();

        if (!$isOnTime && $noveltyIsRequired) {
            if (1 === $applicableWorkShifts->count()) {
                // return novelty types based  punctuality and action
                $noveltyTypes = ($punctuality > 0 && 'start' === $flag) || ($punctuality < 0 && 'end' === $flag)
                    ? $this->noveltyTypeRepository->whereContextType('elegible_by_user')->findForTimeSubtraction()
                    : $this->noveltyTypeRepository->whereContextType('elegible_by_user')->findForTimeAddition();
            } elseif ($applicableWorkShifts->count() > 1) {
                $noveltyTypes = $this->noveltyTypeRepository->whereContextType('elegible_by_user')->get();
            } else {
                // when $applicableWorkShifts->count() === 0
                $noveltyTypes = $this->noveltyTypeRepository->whereContextType('elegible_by_user')->findForTimeAddition();
            }
        }

        $isHoliday = $this->holidayRepository->countWhereIn('date', [$currentDateTime]);

        $noveltyTypes = $noveltyTypes
            ->filter(function (NoveltyType $noveltyType) use ($currentDateTime, $isHoliday) {
                $start = $noveltyType->minStartTimeSlot($currentDateTime);
                $end = $noveltyType->maxEndTimeSlot($currentDateTime);
                $currentDayType = $isHoliday || $currentDateTime->isSunday() ? DayType::Holiday : DayType::Workday;

                return ($noveltyType->isApplicableInAnyTime() || $currentDateTime->between($start, $end))
                    && ($noveltyType->isApplicableInAnyDay() || $noveltyType->apply_on_days_of_type->is($currentDayType));
            })
            ->filter(fn ($n) => ($n->isApplicableInAnyTime() || $n->isApplicableInAnyDay()) || (bool) $n->minStartTimeSlot($currentDateTime) && (bool) $n->maxEndTimeSlot($currentDateTime))
            ->filter(function ($noveltyType) use ($currentDateTime, $flag) {
                $start = $noveltyType->minStartTimeSlot($currentDateTime);
                $end = $noveltyType->maxEndTimeSlot($currentDateTime);
                $closest = $currentDateTime->closest($start, $end);

                return $noveltyType->isApplicableInAnyTime()
                || $noveltyType->isApplicableInAnyDay()
                || $closest->equalTo('start' === $flag ? $start : $end);
            });

        // last selected sub cost centers based on time clock logs
        $subCostCenters = $this->timeClockLogRepository
            ->with(['subCostCenter', 'checkInSubCostCenter', 'checkOutSubCostCenter'])
            ->lastEmployeeLogs($identification->employee->id)
            ->map(function ($timeClockLog) {
                return $timeClockLog->relatedSubCostCenters();
            })
            ->collapse()
            ->unique('id')
            ->values();

        return [
            'action' => 'start' === $flag ? 'check_in' : 'check_out',
            'employee' => ['id' => $identification->employee->id, 'name' => $identification->employee->user->name],
            'punctuality' => $punctuality,
            'work_shifts' => $applicableWorkShifts,
            'novelty_types' => $noveltyTypes,
            'sub_cost_centers' => $subCostCenters,
        ];
    }

    private function noveltyTypeIsRequiredForNonPunctualChecks(): bool
    {
        $requiredNoveltySetting = $this->settingRepository
            ->findByField('key', 'time-clock.require-novelty-type-for-non-punctual-checks')
            ->first();

        $noveltyTypeIsRequired = optional($requiredNoveltySetting)->value;

        return is_null($noveltyTypeIsRequired) ? true : true == $noveltyTypeIsRequired;
    }

    private function adjustScheduledNoveltyTimesBasedOnChecks(): bool
    {
        $adjustNoveltySetting = $this->settingRepository
            ->findByField('key', 'time-clock.adjust-scheduled-novelty-datetime-based-on-checks')
            ->first();

        $noveltyTypeIsRequired = optional($adjustNoveltySetting)->value;

        return true == $noveltyTypeIsRequired;
    }
}
