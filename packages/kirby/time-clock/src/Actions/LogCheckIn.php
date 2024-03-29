<?php

namespace Kirby\TimeClock\Actions;

use Illuminate\Support\Arr;
use Kirby\Company\Contracts\HolidayRepositoryInterface;
use Kirby\Company\Contracts\SubCostCenterRepositoryInterface;
use Kirby\Employees\Contracts\IdentificationRepositoryInterface;
use Kirby\Employees\Models\Identification;
use Kirby\Novelties\Contracts\NoveltyRepositoryInterface;
use Kirby\Novelties\Contracts\NoveltyTypeRepositoryInterface;
use Kirby\Novelties\Enums\NoveltyTypeOperator;
use Kirby\Novelties\Facades\Novelties;
use Kirby\TimeClock\Contracts\SettingRepositoryInterface;
use Kirby\TimeClock\Contracts\TimeClockLogRepositoryInterface;
use Kirby\TimeClock\Exceptions\AlreadyCheckedInException;
use Kirby\TimeClock\Exceptions\CanNotDeductWorkShiftException;
use Kirby\TimeClock\Exceptions\InvalidNoveltyTypeException;
use Kirby\TimeClock\Exceptions\MissingSubCostCenterException;
use Kirby\TimeClock\Exceptions\TooEarlyToCheckException;
use Kirby\TimeClock\Exceptions\TooLateToCheckException;
use Kirby\TimeClock\Models\TimeClockLog;
use Kirby\TimeClock\Traits\CheckInOut;
use Kirby\Users\Models\User;
use Kirby\WorkShifts\Models\WorkShift;

/**
 * Class LogCheckIn.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class LogCheckIn
{
    use CheckInOut;

    /**
     * @var IdentificationRepositoryInterface
     */
    private $identificationRepository;

    /**
     * @var TimeClockLogRepositoryInterface
     */
    private $timeClockLogRepository;

    /**
     * @var NoveltyTypeRepositoryInterface
     */
    private $noveltyTypeRepository;

    /**
     * @var NoveltyRepositoryInterface
     */
    private $noveltyRepository;

    /**
     * @var SubCostCenterRepository
     */
    private $subCostCenterRepository;

    /**
     * @var SettingRepositoryInterface
     */
    private $settingRepository;

    /**
     * @var HolidayRepositoryInterface
     */
    private $holidayRepository;

    /**
     * @var ValidateNoveltyTypeBasedOnWorkShiftPunctuality
     */
    private $validateNoveltyTypeBasedOnWorkShiftPunctualityAction;

    public function __construct(
        HolidayRepositoryInterface $holidayRepository,
        SettingRepositoryInterface $settingRepository,
        NoveltyRepositoryInterface $noveltyRepository,
        NoveltyTypeRepositoryInterface $noveltyTypeRepository,
        TimeClockLogRepositoryInterface $timeClockLogRepository,
        SubCostCenterRepositoryInterface $subCostCenterRepository,
        IdentificationRepositoryInterface $identificationRepository,
        ValidateNoveltyTypeBasedOnWorkShiftPunctuality $validateNoveltyTypeBasedOnWorkShiftPunctualityAction
    ) {
        $this->holidayRepository = $holidayRepository;
        $this->settingRepository = $settingRepository;
        $this->noveltyRepository = $noveltyRepository;
        $this->noveltyTypeRepository = $noveltyTypeRepository;
        $this->timeClockLogRepository = $timeClockLogRepository;
        $this->subCostCenterRepository = $subCostCenterRepository;
        $this->identificationRepository = $identificationRepository;
        $this->validateNoveltyTypeBasedOnWorkShiftPunctualityAction = $validateNoveltyTypeBasedOnWorkShiftPunctualityAction;
    }

    /**
     * @param  int  $workShiftId
     * @param  null|int  $noveltyType
     *
     * @throws InvalidNoveltyTypeException
     * @throws TooEarlyToCheckException
     * @throws TooLateToCheckException
     * @throws MissingSubCostCenterException
     */
    public function run(User $registrar, string $identificationCode, ?int $workShiftId = null, ?int $noveltyTypeId = null, ?int $subCostCenterId = null): TimeClockLog
    {
        $noveltyType = null;
        $subCostCenter = null;
        $checkInOffset = null;
        $scheduledNovelty = null;
        $noveltyTypeIsRequired = $this->noveltyTypeIsRequiredForNonPunctualChecks();

        $identification = $this->identificationRepository
            ->with(['employee.workShifts'])
            ->findByField('code', $identificationCode, ['id', 'employee_id'])
            ->first();

        if ($noveltyTypeId) {
            $noveltyType = $this->noveltyTypeRepository->find($noveltyTypeId);
        }

        if ($noveltyType && $noveltyType->operator->is(NoveltyTypeOperator::Addition) && $subCostCenterId) {
            $subCostCenter = $this->subCostCenterRepository->find($subCostCenterId);
        }

        $this->validateUnfinishedCheckIn($identification);
        $workShift = $this->validateDeductibleWorkShift($identification, $workShiftId);

        if (! $this->noveltyIsValid('start', $workShift, $noveltyType)) {
            throw new InvalidNoveltyTypeException($this->getTimeClockData('start', $identification, $workShiftId));
        }

        $shiftPunctuality = optional($workShift)->slotPunctuality('start', now());
        $timeSlot = optional($workShift)->matchingTimeSlot('start', now());
        $expectedStart = Arr::get($timeSlot, 'start');
        $expectedEnd = Arr::get($timeSlot, 'end');

        // if is not on time, ask for scheduled novelties
        if ($workShift && 0 !== $shiftPunctuality) {
            // scheduled novelties can be discovered until 30 minutes early
            // arrival, early arrivals than 30 minutes for said novelties
            // can't be discovered
            $scheduledNovelty = $this->noveltyRepository
                ->whereScheduledForEmployee(
                    $identification->employee_id,
                    'end_at',
                    $expectedStart,
                    now()->addMinutes(30)
                )
                ->orderBy('start_at', 'DESC')
                ->first(['novelties.*']);

            if ($scheduledNovelty && $this->adjustScheduledNoveltyTimesBasedOnChecks()) {
                $scheduledNovelty = $this->noveltyRepository->update(['end_at' => now()], $scheduledNovelty->id);
            }

            $checkInOffset = optional($scheduledNovelty)->end_at;
            $shiftPunctuality = optional($workShift)->slotPunctuality('start', now(), $checkInOffset);
            $timeSlot = optional($workShift)->matchingTimeSlot('start', now(), $checkInOffset);
            $expectedStart = Arr::get($timeSlot, 'start');
            $expectedEnd = Arr::get($timeSlot, 'end');
        }

        $isTooLate = $shiftPunctuality > 0;
        $isTooEarly = $shiftPunctuality < 0;

        if ($workShift && $isTooEarly && ! $noveltyType && $noveltyTypeIsRequired) {
            throw new TooEarlyToCheckException($this->getTimeClockData('start', $identification, $workShiftId));
        }

        if ($workShift && $isTooLate && ! $noveltyType && $noveltyTypeIsRequired) {
            throw new TooLateToCheckException($this->getTimeClockData('start', $identification, $workShiftId));
        }

        if ($noveltyType && $noveltyType->operator->is(NoveltyTypeOperator::Addition) && ! $subCostCenter) {
            throw new MissingSubCostCenterException($this->getTimeClockData('start', $identification, $workShiftId));
        }

        if ($isTooLate && ! $noveltyTypeId && ! $noveltyTypeIsRequired) {
            $noveltyType = Novelties::defaultSubTractNoveltyType();
        }

        if ($isTooEarly && ! $noveltyTypeId && ! $noveltyTypeIsRequired) {
            $noveltyType = Novelties::defaultAdditionNoveltyType();
        }

        $timeClockLog = [
            'employee_id' => $identification->employee_id,
            'checked_in_at' => now(),
            'expected_check_in_at' => $expectedStart,
            'checked_in_by_id' => $registrar->id,
            'work_shift_id' => optional($workShift)->id,
            'check_in_novelty_type_id' => optional($noveltyType)->id,
            'check_in_sub_cost_center_id' => optional($subCostCenter)->id,
        ];

        return $this->timeClockLogRepository->create($timeClockLog);
    }

    /**
     * @throws AlreadyCheckedInException
     */
    private function validateUnfinishedCheckIn(Identification $identification): void
    {
        $lastCheckIn = $this->timeClockLogRepository->lastCheckInWithOutCheckOutFromEmployeeId(
            $identification->employee_id,
            ['id', 'checked_in_at']
        );

        if ($lastCheckIn) {
            throw new AlreadyCheckedInException($lastCheckIn->checked_in_at);
        }
    }

    /**
     * @throws CanNotDeductWorkShiftException
     */
    private function validateDeductibleWorkShift(Identification $identification, ?int $workShiftId): ?WorkShift
    {
        $deductedWorkShifts = $this->getApplicableWorkShifts($identification, $workShiftId);

        if ($foundWorkShift = $deductedWorkShifts->firstWhere('id', $workShiftId)) {
            return $foundWorkShift;
        }

        if ($deductedWorkShifts->count() > 1) {
            $deductedWorkShifts = $deductedWorkShifts
                ->filter(function ($shift) {
                    $now = now();
                    $timeSlot = $shift->matchingTimeSlot('start', $now);

                    return $now
                        ->closest($timeSlot['start'], $timeSlot['end'])
                        ->equalTo($timeSlot['start']);
                });
        }

        $employeeWorkShiftsCount = $identification->employee->workShifts->count();

        $hasWorkShiftsButCantBeDeducted = $employeeWorkShiftsCount > 0 && 0 === $deductedWorkShifts->count();

        if (($hasWorkShiftsButCantBeDeducted || $deductedWorkShifts->count() > 1) && -1 !== $workShiftId) {
            throw new CanNotDeductWorkShiftException($this->getTimeClockData('start', $identification, $workShiftId));
        }

        return $deductedWorkShifts->first();
    }
}
