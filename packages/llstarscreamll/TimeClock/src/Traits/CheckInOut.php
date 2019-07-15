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

        return $deductedWorkShifts;
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

        if ($workShift && $shiftPunctuality > 0 && $noveltyType && !$noveltyType->operator->is($lateNoveltyOperator)) {
            $isValid = false;
        }

        if ($workShift && $shiftPunctuality < 0 && $noveltyType && !$noveltyType->operator->is($eagerNoveltyOperator)) {
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
    protected function getTimeClockData(string $flag, Identification $identification, ?int $workShiftId): array
    {
        $applicableWorkShifts = $this->getApplicableWorkShifts($identification, $workShiftId);
        $workShift = $applicableWorkShifts->first();
        $punctuality = $applicableWorkShifts->count() === 1 ? optional($workShift)->slotPunctuality($flag, now()) : null;

        // return all novelty types if punctuality wasn't solved
        if (is_null($punctuality)) {
            $noveltyTypes = $this->noveltyTypeRepository->all();
        } else {
            // return novelty types based  punctuality and action
            $noveltyTypes = ($punctuality > 0 && $flag === 'start') || ($punctuality < 0 && $flag === 'end')
                ? $this->noveltyTypeRepository->findForTimeSubtraction()
                : $this->noveltyTypeRepository->findForTimeAddition();
        }

        // last selected sub cost centers based on time clock logs
        $subCostCenters = $this->timeClockLogRepository
            ->lastEmployeeLogs($identification->employee->id)
            ->map(function ($timeClockLog) {
                return $timeClockLog->relatedSubCostCenters();
            })->collapse();

        return [
            'action' => 'check_in',
            'employee' => ['id' => $identification->employee->id, 'name' => $identification->employee->user->name],
            'punctuality' => $punctuality,
            'work_shifts' => $applicableWorkShifts,
            'novelty_types' => $noveltyTypes,
            'sub_cost_centers' => $subCostCenters,
        ];
    }
}
