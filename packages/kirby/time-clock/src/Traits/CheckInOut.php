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
     * @return bool
     */
    private function subtractNoveltyTypeIsRequired(): bool
    {
        $requiredNoveltySetting = $this->settingRepository
            ->findByField('key', 'time-clock.require-subtract-novelty-type-on-checks')
            ->first();

        $noveltyTypeIsRequired = optional($requiredNoveltySetting)->value;

        return is_null($noveltyTypeIsRequired) ? true : $noveltyTypeIsRequired == true;
    }

    /**
     * @return bool
     */
    private function adjustScheduledNoveltyTimesBasedOnChecks(): bool
    {
        $adjustNoveltySetting = $this->settingRepository
            ->findByField('key', 'time-clock.adjust-scheduled-novelties-times-based-on-checks')
            ->first();

        $noveltyTypeIsRequired = optional($adjustNoveltySetting)->value;

        return $noveltyTypeIsRequired == true;
    }

    /**
     * @param Identification $identification
     * @param int            $workShiftId
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
     * @param  string           $flag
     * @param  WorkShift        $workShift
     * @param  NoveltyType|null $noveltyType
     * @return mixed
     */
    protected function noveltyIsValid(string $flag, ?WorkShift $workShift, ?NoveltyType $noveltyType = null): bool
    {
        $isValid = true;
        $shiftPunctuality = optional($workShift)->slotPunctuality($flag, now());

        $lateNoveltyOperator = $flag === 'start' ? NoveltyTypeOperator::Subtraction : NoveltyTypeOperator::Addition;
        $eagerNoveltyOperator = $flag === 'start' ? NoveltyTypeOperator::Addition : NoveltyTypeOperator::Subtraction;

        if ($workShift && $shiftPunctuality > 0 && $noveltyType && ! $noveltyType->operator->is($lateNoveltyOperator)) {
            $isValid = false;
        }

        if ($workShift && $shiftPunctuality < 0 && $noveltyType && ! $noveltyType->operator->is($eagerNoveltyOperator)) {
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * @param  string         $flag
     * @param  Identification $identification
     * @param  int            $workShiftId
     * @return array
     */
    protected function getTimeClockData(string $flag, Identification $identification, ?int $workShiftId = null): array
    {
        $currentDateTime = now();
        $targetFlag = $flag === 'start' ? 'end' : 'start';
        $noveltyAttr = "scheduled_{$targetFlag}_at";

        $scheduledNovelty = $this->noveltyRepository
            ->whereScheduledForEmployee($identification->employee_id, $noveltyAttr, now()->subHour(), now()->endOfDay())
            ->orderBy('id', 'DESC')
            ->first();

        $checkOffset = optional($scheduledNovelty)->$noveltyAttr;

        $applicableWorkShifts = $this->getApplicableWorkShifts($identification, $workShiftId);
        $workShift = $applicableWorkShifts->first();
        $punctuality = $applicableWorkShifts->count() === 1 ? optional($workShift)->slotPunctuality($flag, $currentDateTime, $checkOffset) : null;

        $isOnTime = $punctuality === 0;
        $noveltyTypes = new Collection([]);
        $noveltyIsRequired = $this->subtractNoveltyTypeIsRequired();

        if (! $isOnTime && $noveltyIsRequired) {
            if ($applicableWorkShifts->count() === 1) {
                // return novelty types based  punctuality and action
                $noveltyTypes = ($punctuality > 0 && $flag === 'start') || ($punctuality < 0 && $flag === 'end')
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

                return ($noveltyType->isApplicableInAnyTime() || $currentDateTime->between($start, $end)) &&
                    ($noveltyType->isApplicableInAnyDay() || $noveltyType->apply_on_days_of_type->is($currentDayType));
            })
            ->filter(fn ($n) => ($n->isApplicableInAnyTime() || $n->isApplicableInAnyDay()) || (bool) $n->minStartTimeSlot($currentDateTime) && (bool) $n->maxEndTimeSlot($currentDateTime))
            ->filter(function ($noveltyType) use ($currentDateTime, $flag) {
                $start = $noveltyType->minStartTimeSlot($currentDateTime);
                $end = $noveltyType->maxEndTimeSlot($currentDateTime);
                $closest = $currentDateTime->closest($start, $end);

                return $noveltyType->isApplicableInAnyTime() ||
                $noveltyType->isApplicableInAnyDay() ||
                $closest->equalTo($flag === 'start' ? $start : $end);
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
            'action' => $flag === 'start' ? 'check_in' : 'check_out',
            'employee' => ['id' => $identification->employee->id, 'name' => $identification->employee->user->name],
            'punctuality' => $punctuality,
            'work_shifts' => $applicableWorkShifts,
            'novelty_types' => $noveltyTypes,
            'sub_cost_centers' => $subCostCenters,
        ];
    }
}
