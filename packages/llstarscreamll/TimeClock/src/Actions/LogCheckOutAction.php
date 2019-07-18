<?php

namespace llstarscreamll\TimeClock\Actions;

use llstarscreamll\Users\Models\User;
use llstarscreamll\TimeClock\Traits\CheckInOut;
use llstarscreamll\TimeClock\Models\TimeClockLog;
use llstarscreamll\Novelties\Enums\NoveltyTypeOperator;
use llstarscreamll\TimeClock\Exceptions\MissingCheckInException;
use llstarscreamll\TimeClock\Exceptions\TooLateToCheckException;
use llstarscreamll\TimeClock\Exceptions\TooEarlyToCheckException;
use llstarscreamll\TimeClock\Exceptions\InvalidNoveltyTypeException;
use llstarscreamll\Company\Contracts\SubCostCenterRepositoryInterface;
use llstarscreamll\Novelties\Contracts\NoveltyTypeRepositoryInterface;
use llstarscreamll\TimeClock\Exceptions\MissingSubCostCenterException;
use llstarscreamll\TimeClock\Contracts\TimeClockLogRepositoryInterface;
use llstarscreamll\Employees\Contracts\IdentificationRepositoryInterface;

/**
 * Class LogCheckOutAction.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class LogCheckOutAction
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
     * @var SubCostCenterRepositoryInterface
     */
    private $subCostCenterRepository;

    /**
     * @var ValidateNoveltyTypeBasedOnWorkShiftPunctualityAction
     */
    private $validateNoveltyTypeBasedOnWorkShiftPunctualityAction;

    /**
     * @param NoveltyTypeRepositoryInterface                       $noveltyTypeRepository
     * @param TimeClockLogRepositoryInterface                      $timeClockLogRepository
     * @param SubCostCenterRepositoryInterface                     $subCostCenterRepository
     * @param IdentificationRepositoryInterface                    $identificationRepository
     * @param ValidateNoveltyTypeBasedOnWorkShiftPunctualityAction $validateNoveltyTypeBasedOnWorkShiftPunctualityAction
     */
    public function __construct(
        NoveltyTypeRepositoryInterface $noveltyTypeRepository,
        TimeClockLogRepositoryInterface $timeClockLogRepository,
        SubCostCenterRepositoryInterface $subCostCenterRepository,
        IdentificationRepositoryInterface $identificationRepository,
        ValidateNoveltyTypeBasedOnWorkShiftPunctualityAction $validateNoveltyTypeBasedOnWorkShiftPunctualityAction
    ) {
        $this->noveltyTypeRepository = $noveltyTypeRepository;
        $this->timeClockLogRepository = $timeClockLogRepository;
        $this->subCostCenterRepository = $subCostCenterRepository;
        $this->identificationRepository = $identificationRepository;
        $this->validateNoveltyTypeBasedOnWorkShiftPunctualityAction = $validateNoveltyTypeBasedOnWorkShiftPunctualityAction;
    }

    /**
     * @param  User                            $registrar
     * @param  string                          $identificationCode
     * @param  int                             $subCostCenterId
     * @param  int                             $noveltyTypeId
     * @param  int                             $noveltySubCostCenterId
     * @throws MissingCheckInException
     * @throws TooEarlyToCheckException
     * @throws TooLateToCheckException
     * @throws InvalidNoveltyTypeException
     * @throws MissingSubCostCenterException
     * @return TimeClockLog
     */
    public function run(User $registrar, string $identificationCode, ?int $subCostCenterId, ?int $noveltyTypeId, ?int $noveltySubCostCenterId): TimeClockLog
    {
        $noveltyType = null;

        $identification = $this->identificationRepository
            ->findByField('code', $identificationCode, ['id', 'employee_id'])
            ->first();

        if ($noveltyTypeId) {
            $noveltyType = $this->noveltyTypeRepository->find($noveltyTypeId);
        }

        $lastCheckIn = $this->timeClockLogRepository->lastCheckInWithOutCheckOutFromEmployeeId(
            $identification->employee_id,
            ['id', 'work_shift_id', 'checked_in_at']
        );

        if (! $lastCheckIn) {
            throw new MissingCheckInException();
        }

        if ($lastCheckIn->requireSubCostCenter(now()) && ! $subCostCenterId) {
            throw new MissingSubCostCenterException($this->getTimeClockData('end', $identification));
        }

        $workShift = $lastCheckIn->workShift;

        if ($noveltyType && $noveltyType->operator->is(NoveltyTypeOperator::Addition) && ! $subCostCenterId) {
            throw new MissingSubCostCenterException($this->getTimeClockData('end', $identification, $workShift->id));
        }

        $shiftPunctuality = optional($workShift)->slotPunctuality('end', now());

        if (! $this->noveltyIsValid('end', $workShift, $noveltyType)) {
            throw new InvalidNoveltyTypeException($this->getTimeClockData('end', $identification, $workShift->id));
        }

        if ($workShift && $shiftPunctuality < 0 && ! $noveltyType) {
            throw new TooEarlyToCheckException($this->getTimeClockData('end', $identification, $workShift->id));
        }

        if ($workShift && $shiftPunctuality > 0 && ! $noveltyType) {
            throw new TooLateToCheckException($this->getTimeClockData('end', $identification, $workShift->id));
        }

        $timeClockLogUpdate = [
            'checked_out_at' => now(),
            'checked_out_by_id' => $registrar->id,
            'check_out_novelty_type_id' => optional($noveltyType)->id,
            'check_out_sub_cost_center_id' => $noveltySubCostCenterId,
            'sub_cost_center_id' => $subCostCenterId,
        ];

        return $this->timeClockLogRepository->update($timeClockLogUpdate, $lastCheckIn->id);
    }
}
