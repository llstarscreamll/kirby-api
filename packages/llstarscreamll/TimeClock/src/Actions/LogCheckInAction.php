<?php

namespace llstarscreamll\TimeClock\Actions;

use llstarscreamll\Users\Models\User;
use llstarscreamll\TimeClock\Models\TimeClockLog;
use llstarscreamll\TimeClock\Exceptions\TooLateToCheckException;
use llstarscreamll\TimeClock\Exceptions\TooEarlyToCheckException;
use llstarscreamll\TimeClock\Exceptions\AlreadyCheckedInException;
use llstarscreamll\Novelties\Contracts\NoveltyTypeRepositoryInterface;
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
     * @param NoveltyTypeRepositoryInterface    $noveltyTypeRepository
     * @param IdentificationRepositoryInterface $identificationRepository
     * @param TimeClockLogRepositoryInterface   $timeClockLogRepository
     */
    public function __construct(
        NoveltyTypeRepositoryInterface $noveltyTypeRepository,
        TimeClockLogRepositoryInterface $timeClockLogRepository,
        IdentificationRepositoryInterface $identificationRepository
    ) {
        $this->noveltyTypeRepository = $noveltyTypeRepository;
        $this->timeClockLogRepository = $timeClockLogRepository;
        $this->identificationRepository = $identificationRepository;
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
    public function run(User $registrar, string $identificationCode, int $workShiftId = null): TimeClockLog
    {
        $identification = $this->identificationRepository
            ->with(['employee.workShifts'])
            ->findByField('code', $identificationCode, ['id', 'employee_id'])
            ->first();

        $lastCheckIn = $this->timeClockLogRepository->lastCheckInWithOutCheckOutFromEmployeeId(
            $identification->employee_id,
            ['id', 'checked_in_at']
        );

        if ($lastCheckIn) {
            throw new AlreadyCheckedInException('Ya se registra una entrada.', $lastCheckIn->checked_in_at);
        }

        $workShifts = $identification
            ->employee
            ->getWorkShiftsThatMatchesTime(now());

        if ($workShiftId) {
            $workShifts = $identification
                ->employee
                ->workShifts
                ->where('id', $workShiftId);
        }

        if ($workShifts->count() > 1) {
            throw new CanNotDeductWorkShiftException('No fue posible deducir el turno.', $workShifts);
        }

        $workShift = $workShifts->first();

        if ($workShift && $workShift->isOnTimeToStart() < 0) {
            $noveltyTypes = $this->noveltyTypeRepository->findForTimeAddition();
            throw new TooEarlyToCheckException('Es temprano para registrar la entrada.', $noveltyTypes);
        }

        if ($workShift && $workShift->isOnTimeToStart() > 0) {
            $noveltyTypes = $this->noveltyTypeRepository->findForTimeSubtraction();
            throw new TooLateToCheckException('Es tarde para registrar la entrada.', $noveltyTypes);
        }

        $timeClockLog = [
            'employee_id' => $identification->employee_id,
            'checked_in_at' => now(),
            'checked_in_by_id' => $registrar->id,
            'work_shift_id' => optional($workShift)->id,
        ];

        return $this->timeClockLogRepository->create($timeClockLog);
    }
}
