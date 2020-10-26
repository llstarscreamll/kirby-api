<?php

namespace Kirby\TimeClock\Actions;

use Illuminate\Support\Arr;
use Kirby\Company\Contracts\HolidayRepositoryInterface;
use Kirby\Company\Contracts\SubCostCenterRepositoryInterface;
use Kirby\Employees\Contracts\IdentificationRepositoryInterface;
use Kirby\Novelties\Contracts\NoveltyRepositoryInterface;
use Kirby\Novelties\Contracts\NoveltyTypeRepositoryInterface;
use Kirby\Novelties\Enums\NoveltyTypeOperator;
use Kirby\TimeClock\Contracts\SettingRepositoryInterface;
use Kirby\TimeClock\Contracts\TimeClockLogRepositoryInterface;
use Kirby\TimeClock\Exceptions\InvalidNoveltyTypeException;
use Kirby\TimeClock\Exceptions\MissingCheckInException;
use Kirby\TimeClock\Exceptions\MissingSubCostCenterException;
use Kirby\TimeClock\Exceptions\TooEarlyToCheckException;
use Kirby\TimeClock\Exceptions\TooLateToCheckException;
use Kirby\TimeClock\Models\TimeClockLog;
use Kirby\TimeClock\Traits\CheckInOut;
use Kirby\Users\Models\User;

/**
 * Class LogCheckOut.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class LogCheckOut
{
    use CheckInOut;

    /**
     * @var SettingRepositoryInterface
     */
    private $settingRepository;

    /**
     * @var NoveltyRepositoryInterface
     */
    private $noveltyRepository;

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
     * @var HolidayRepositoryInterface
     */
    private $holidayRepository;

    /**
     * @var ValidateNoveltyTypeBasedOnWorkShiftPunctuality
     */
    private $validateNoveltyTypeBasedOnWorkShiftPunctualityAction;

    /**
     * @param HolidayRepositoryInterface                     $holidayRepository
     * @param SettingRepositoryInterface                     $settingRepository
     * @param NoveltyRepositoryInterface                     $noveltyRepository
     * @param NoveltyTypeRepositoryInterface                 $noveltyTypeRepository
     * @param TimeClockLogRepositoryInterface                $timeClockLogRepository
     * @param SubCostCenterRepositoryInterface               $subCostCenterRepository
     * @param IdentificationRepositoryInterface              $identificationRepository
     * @param ValidateNoveltyTypeBasedOnWorkShiftPunctuality $validateNoveltyTypeBasedOnWorkShiftPunctualityAction
     */
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
        $noveltyTypeIsRequired = $this->noveltyTypeIsRequiredForNonPunctualChecks();

        $identification = $this->identificationRepository
            ->findByField('code', $identificationCode, ['id', 'employee_id'])
            ->first();

        $scheduledNovelty = $this->noveltyRepository
            ->whereScheduledForEmployee($identification->employee_id, 'start_at', now(), now()->endOfDay())
            ->orderBy('id', 'DESC')
            ->first(['novelties.*']);
        if ($scheduledNovelty && $this->adjustScheduledNoveltyTimesBasedOnChecks()) {
            $scheduledNovelty = $this->noveltyRepository->update(
                [
                    'start_at' => now(),
                ],
                $scheduledNovelty->id
            );
        }

        $checkOutOffset = optional($scheduledNovelty)->start_at;

        if ($noveltyTypeId) {
            $noveltyType = $this->noveltyTypeRepository->find($noveltyTypeId);
        }

        $lastCheckIn = $this->timeClockLogRepository
            ->with(['workShift'])
            ->lastCheckInWithOutCheckOutFromEmployeeId($identification->employee_id);

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

        $shiftPunctuality = optional($workShift)->slotPunctuality('end', now(), $checkOutOffset);
        $timeSlot = optional($workShift)->matchingTimeSlot('end', now(), $checkOutOffset);
        $expectedEnd = Arr::get($timeSlot, 'end');
        $isTooEarly = $shiftPunctuality < 0;
        $isTooLate = $shiftPunctuality > 0;

        if (! $this->noveltyIsValid('end', $workShift, $noveltyType)) {
            throw new InvalidNoveltyTypeException($this->getTimeClockData('end', $identification, $workShift->id));
        }

        if ($workShift && $isTooEarly && ! $noveltyType && $noveltyTypeIsRequired) {
            throw new TooEarlyToCheckException($this->getTimeClockData('end', $identification, $workShift->id));
        }

        if ($workShift && $isTooLate && ! $noveltyType && $noveltyTypeIsRequired) {
            throw new TooLateToCheckException($this->getTimeClockData('end', $identification, $workShift->id));
        }

        if (! $noveltyTypeId && $isTooEarly && ! $noveltyTypeIsRequired) {
            $noveltyType = $this->noveltyTypeRepository->findDefaultForSubtraction();
        }

        if (! $noveltyTypeId && $isTooLate && ! $noveltyTypeIsRequired) {
            $noveltyType = $this->noveltyTypeRepository->findDefaultForAddition();
        }

        $timeClockLogUpdate = [
            'checked_out_at' => now(),
            'expected_check_out_at' => $expectedEnd,
            'checked_out_by_id' => $registrar->id,
            'sub_cost_center_id' => $subCostCenterId,
            'check_out_novelty_type_id' => optional($noveltyType)->id,
            'check_out_sub_cost_center_id' => ($noveltyTypeIsRequired && $shiftPunctuality !== 0) || $shiftPunctuality === 0
                ? $noveltySubCostCenterId
                : $subCostCenterId,
        ];

        return $this->timeClockLogRepository->update($timeClockLogUpdate, $lastCheckIn->id);
    }
}
