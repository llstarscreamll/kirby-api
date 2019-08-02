<?php

namespace llstarscreamll\TimeClock\Traits;

use Illuminate\Support\Collection;
use llstarscreamll\WorkShifts\Models\WorkShift;
use llstarscreamll\Novelties\Models\NoveltyType;
use llstarscreamll\Employees\Models\Identification;
use llstarscreamll\Novelties\Enums\NoveltyTypeOperator;

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
            ->findByField('key', 'time-clock.require-novelty-type-on-late-check-in')
            ->first();

        $noveltyTypeIsRequired = optional($requiredNoveltySetting)->value;

        return is_null($noveltyTypeIsRequired) ? true : $noveltyTypeIsRequired == true;
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
        $applicableWorkShifts = $this->getApplicableWorkShifts($identification, $workShiftId);
        $workShift = $applicableWorkShifts->first();
        $punctuality = $applicableWorkShifts->count() === 1 ? optional($workShift)->slotPunctuality($flag, now()) : null;

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

        // last selected sub cost centers based on time clock logs
        $subCostCenters = $this->timeClockLogRepository
            ->lastEmployeeLogs($identification->employee->id)
            ->map(function ($timeClockLog) {
                return $timeClockLog->relatedSubCostCenters();
            })->collapse()
            ->unique('id');

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
