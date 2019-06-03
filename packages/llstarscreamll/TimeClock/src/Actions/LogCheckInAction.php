<?php

namespace llstarscreamll\TimeClock\Actions;

use llstarscreamll\Users\Models\User;
use llstarscreamll\WorkShifts\Models\WorkShift;
use llstarscreamll\Novelties\Models\NoveltyType;
use llstarscreamll\TimeClock\Models\TimeClockLog;
use llstarscreamll\Employees\Models\Identification;
use llstarscreamll\TimeClock\Exceptions\TooLateToCheckException;
use llstarscreamll\TimeClock\Exceptions\AlreadyCheckedInException;
use llstarscreamll\Novelties\Contracts\NoveltyTypeRepositoryInterface;
use llstarscreamll\TimeClock\Contracts\TimeClockLogRepositoryInterface;
use llstarscreamll\TimeClock\Exceptions\CanNotDeductWorkShiftException;
use llstarscreamll\Employees\Contracts\IdentificationRepositoryInterface;
use llstarscreamll\TimeClock\Actions\ValidateNoveltyTypeBasedOnWorkShiftPunctualityAction;

/**
 * Class LogCheckInAction.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class LogCheckInAction
{
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
     * @var ValidateNoveltyTypeBasedOnWorkShiftPunctualityAction
     */
    private $validateNoveltyTypeBasedOnWorkShiftPunctualityAction;

    /**
     * @param NoveltyTypeRepositoryInterface    $noveltyTypeRepository
     * @param IdentificationRepositoryInterface $identificationRepository
     * @param TimeClockLogRepositoryInterface   $timeClockLogRepository
     */
    public function __construct(
        NoveltyTypeRepositoryInterface $noveltyTypeRepository,
        TimeClockLogRepositoryInterface $timeClockLogRepository,
        IdentificationRepositoryInterface $identificationRepository,
        ValidateNoveltyTypeBasedOnWorkShiftPunctualityAction $validateNoveltyTypeBasedOnWorkShiftPunctualityAction
    ) {
        $this->noveltyTypeRepository = $noveltyTypeRepository;
        $this->timeClockLogRepository = $timeClockLogRepository;
        $this->identificationRepository = $identificationRepository;
        $this->validateNoveltyTypeBasedOnWorkShiftPunctualityAction = $validateNoveltyTypeBasedOnWorkShiftPunctualityAction;
    }

    /**
     * @param  User                             $registrar
     * @param  string                           $identificationCode
     * @param  int                              $workShiftId
     * @throws AlreadyCheckedInException
     * @throws CanNotDeductWorkShiftException
     * @throws TooLateToCheckException
     * @return TimeClockLog
     */
    public function run(User $registrar, string $identificationCode, int $workShiftId = null, array $novelty = null): TimeClockLog
    {
        $identification = $this->identificationRepository
            ->with(['employee.workShifts'])
            ->findByField('code', $identificationCode, ['id', 'employee_id'])
            ->first();

        $this->validateUnfinishedCheckIn($identification);
        $workShift = $this->validateDeductibleWorkShift($identification, $workShiftId, $novelty);
        $novelty = $this->validateNoveltyTypeBasedOnWorkShiftPunctualityAction->run(
            'start', $workShift, $novelty
        );

        $timeClockLog = [
            'employee_id' => $identification->employee_id,
            'checked_in_at' => now(),
            'checked_in_by_id' => $registrar->id,
            'work_shift_id' => optional($workShift)->id,
            'check_in_novelty_type_id' => optional($novelty)->id,
        ];

        return $this->timeClockLogRepository->create($timeClockLog);
    }

    /**
     * @param Identification $identification
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
     * @param  Identification   $identification
     * @param  null|int         $workShiftId
     * @return null|WorkShift
     */
    private function validateDeductibleWorkShift(Identification $identification, ?int $workShiftId): ?WorkShift
    {
        $workShifts = $identification
            ->employee
            ->getWorkShiftsThatMatchesTime(now());

        $employeeWorkShiftsCount = $identification->employee->workShifts->count();

        if ($workShiftId) {
            $workShifts = $identification
                ->employee
                ->workShifts
                ->where('id', $workShiftId);
        }

        $hasWorkShiftsButCantBeDeducted = $employeeWorkShiftsCount > 0 && $workShifts->count() === 0;

        if ($hasWorkShiftsButCantBeDeducted || $workShifts->count() > 1) {
            throw new CanNotDeductWorkShiftException(null, $workShifts);
        }

        return $workShifts->first();
    }
}
