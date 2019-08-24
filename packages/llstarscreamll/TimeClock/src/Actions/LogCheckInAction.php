<?php

namespace llstarscreamll\TimeClock\Actions;

use llstarscreamll\Users\Models\User;
use llstarscreamll\TimeClock\Traits\CheckInOut;
use llstarscreamll\WorkShifts\Models\WorkShift;
use llstarscreamll\Novelties\Models\NoveltyType;
use llstarscreamll\TimeClock\Models\TimeClockLog;
use llstarscreamll\Employees\Models\Identification;
use llstarscreamll\Novelties\Enums\NoveltyTypeOperator;
use llstarscreamll\TimeClock\Exceptions\TooLateToCheckException;
use llstarscreamll\TimeClock\Exceptions\TooEarlyToCheckException;
use llstarscreamll\Novelties\Contracts\NoveltyRepositoryInterface;
use llstarscreamll\TimeClock\Contracts\SettingRepositoryInterface;
use llstarscreamll\TimeClock\Exceptions\AlreadyCheckedInException;
use llstarscreamll\TimeClock\Exceptions\InvalidNoveltyTypeException;
use llstarscreamll\Company\Contracts\SubCostCenterRepositoryInterface;
use llstarscreamll\Novelties\Contracts\NoveltyTypeRepositoryInterface;
use llstarscreamll\TimeClock\Exceptions\MissingSubCostCenterException;
use llstarscreamll\TimeClock\Contracts\TimeClockLogRepositoryInterface;
use llstarscreamll\TimeClock\Exceptions\CanNotDeductWorkShiftException;
use llstarscreamll\Employees\Contracts\IdentificationRepositoryInterface;

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
     * @var ValidateNoveltyTypeBasedOnWorkShiftPunctualityAction
     */
    private $validateNoveltyTypeBasedOnWorkShiftPunctualityAction;

    /**
     * @param SettingRepositoryInterface                           $settingRepository
     * @param NoveltyRepositoryInterface                           $noveltyRepository
     * @param NoveltyTypeRepositoryInterface                       $noveltyTypeRepository
     * @param TimeClockLogRepositoryInterface                      $timeClockLogRepository
     * @param SubCostCenterRepositoryInterface                     $subCostCenterRepository
     * @param IdentificationRepositoryInterface                    $identificationRepository
     * @param ValidateNoveltyTypeBasedOnWorkShiftPunctualityAction $validateNoveltyTypeBasedOnWorkShiftPunctualityAction
     */
    public function __construct(
        SettingRepositoryInterface $settingRepository,
        NoveltyRepositoryInterface $noveltyRepository,
        NoveltyTypeRepositoryInterface $noveltyTypeRepository,
        TimeClockLogRepositoryInterface $timeClockLogRepository,
        SubCostCenterRepositoryInterface $subCostCenterRepository,
        IdentificationRepositoryInterface $identificationRepository,
        ValidateNoveltyTypeBasedOnWorkShiftPunctualityAction $validateNoveltyTypeBasedOnWorkShiftPunctualityAction
    ) {
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

        $scheduledNovelty = $this->noveltyRepository->whereScheduledForEmployee($identification->employee->id, 'end_at', now()->startOfDay(), now())->first();
        $checkInOffset = optional($scheduledNovelty)->end_at;

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

        $shiftPunctuality = optional($workShift)->slotPunctuality('start', now(), $checkInOffset);

        if ($workShift && $shiftPunctuality < 0 && ! $noveltyType) {
            throw new TooEarlyToCheckException($this->getTimeClockData('start', $identification, $workShiftId));
        }

        if ($workShift && $shiftPunctuality > 0 && ! $noveltyType && $noveltyTypeIsRequired) {
            throw new TooLateToCheckException($this->getTimeClockData('start', $identification, $workShiftId));
        }

        if ($noveltyType && $noveltyType->operator->is(NoveltyTypeOperator::Addition) && ! $subCostCenter) {
            throw new MissingSubCostCenterException($this->getTimeClockData('start', $identification, $workShiftId));
        }

        if (! $noveltyTypeId && $shiftPunctuality > 0 && ! $noveltyTypeIsRequired) {
            $noveltyType = $this->noveltyTypeRepository->findDefaultForSubtraction();
        }

        $timeClockLog = [
            'employee_id' => $identification->employee_id,
            'checked_in_at' => now(),
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
