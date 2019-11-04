<?php

namespace llstarscreamll\TimeClock\Actions;

use Illuminate\Support\Arr;
use llstarscreamll\Company\Contracts\HolidayRepositoryInterface;
use llstarscreamll\Company\Contracts\SubCostCenterRepositoryInterface;
use llstarscreamll\Employees\Contracts\IdentificationRepositoryInterface;
use llstarscreamll\Employees\Models\Identification;
use llstarscreamll\Novelties\Contracts\NoveltyRepositoryInterface;
use llstarscreamll\Novelties\Contracts\NoveltyTypeRepositoryInterface;
use llstarscreamll\Novelties\Enums\NoveltyTypeOperator;
use llstarscreamll\TimeClock\Contracts\SettingRepositoryInterface;
use llstarscreamll\TimeClock\Contracts\TimeClockLogRepositoryInterface;
use llstarscreamll\TimeClock\Exceptions\AlreadyCheckedInException;
use llstarscreamll\TimeClock\Exceptions\CanNotDeductWorkShiftException;
use llstarscreamll\TimeClock\Exceptions\InvalidNoveltyTypeException;
use llstarscreamll\TimeClock\Exceptions\MissingSubCostCenterException;
use llstarscreamll\TimeClock\Exceptions\TooEarlyToCheckException;
use llstarscreamll\TimeClock\Exceptions\TooLateToCheckException;
use llstarscreamll\TimeClock\Models\TimeClockLog;
use llstarscreamll\TimeClock\Traits\CheckInOut;
use llstarscreamll\Users\Models\User;
use llstarscreamll\WorkShifts\Models\WorkShift;

/**
 * Class LogCheckInAction.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class LogCheckInAction
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
     * @var ValidateNoveltyTypeBasedOnWorkShiftPunctualityAction
     */
    private $validateNoveltyTypeBasedOnWorkShiftPunctualityAction;

    /**
     * @param HolidayRepositoryInterface                           $holidayRepository
     * @param SettingRepositoryInterface                           $settingRepository
     * @param NoveltyRepositoryInterface                           $noveltyRepository
     * @param NoveltyTypeRepositoryInterface                       $noveltyTypeRepository
     * @param TimeClockLogRepositoryInterface                      $timeClockLogRepository
     * @param SubCostCenterRepositoryInterface                     $subCostCenterRepository
     * @param IdentificationRepositoryInterface                    $identificationRepository
     * @param ValidateNoveltyTypeBasedOnWorkShiftPunctualityAction $validateNoveltyTypeBasedOnWorkShiftPunctualityAction
     */
    public function __construct(
        HolidayRepositoryInterface $holidayRepository,
        SettingRepositoryInterface $settingRepository,
        NoveltyRepositoryInterface $noveltyRepository,
        NoveltyTypeRepositoryInterface $noveltyTypeRepository,
        TimeClockLogRepositoryInterface $timeClockLogRepository,
        SubCostCenterRepositoryInterface $subCostCenterRepository,
        IdentificationRepositoryInterface $identificationRepository,
        ValidateNoveltyTypeBasedOnWorkShiftPunctualityAction $validateNoveltyTypeBasedOnWorkShiftPunctualityAction
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
     * @param  User                            $registrar
     * @param  string                          $identificationCode
     * @param  int                             $workShiftId
     * @param  null|int                        $noveltyType
     * @param  null|int                        $subCostCenterId
     * @throws InvalidNoveltyTypeException
     * @throws TooEarlyToCheckException
     * @throws TooLateToCheckException
     * @throws MissingSubCostCenterException
     * @return TimeClockLog
     */
    public function run(User $registrar, string $identificationCode, ?int $workShiftId = null, ?int $noveltyTypeId = null, ?int $subCostCenterId = null): TimeClockLog
    {
        $noveltyType = null;
        $subCostCenter = null;
        $checkInOffset = null;
        $scheduledNovelty = null;
        $noveltyTypeIsRequired = $this->subtractNoveltyTypeIsRequired();

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

        // if is not on time, ask for past novelties
        if ($workShift && $shiftPunctuality !== 0) {
            $scheduledNovelty = $this->noveltyRepository
                ->whereScheduledForEmployee($identification->employee->id, 'scheduled_end_at', $expectedStart, $expectedEnd)
                ->orderBy('created_at', 'DESC')
                ->first();

            $checkInOffset = optional($scheduledNovelty)->scheduled_end_at;
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
            $noveltyType = $this->noveltyTypeRepository->findDefaultForSubtraction();
        }

        if ($isTooEarly && ! $noveltyTypeId && ! $noveltyTypeIsRequired) {
            $noveltyType = $this->noveltyTypeRepository->findDefaultForAddition();
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
     * @param  Identification              $identification
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
     * @param  Identification                   $identification
     * @param  null|int                         $workShiftId
     * @throws CanNotDeductWorkShiftException
     * @return null|WorkShift
     */
    private function validateDeductibleWorkShift(Identification $identification, ?int $workShiftId): ?WorkShift
    {
        $deductedWorkShifts = $this->getApplicableWorkShifts($identification, $workShiftId);

        $employeeWorkShiftsCount = $identification->employee->workShifts->count();

        $hasWorkShiftsButCantBeDeducted = $employeeWorkShiftsCount > 0 && $deductedWorkShifts->count() === 0;

        if (($hasWorkShiftsButCantBeDeducted || $deductedWorkShifts->count() > 1) && $workShiftId !== -1) {
            throw new CanNotDeductWorkShiftException($this->getTimeClockData('start', $identification, $workShiftId));
        }

        return $deductedWorkShifts->first();
    }
}
