<?php

namespace llstarscreamll\TimeClock\Actions;

use llstarscreamll\Users\Models\User;
use llstarscreamll\TimeClock\Models\TimeClockLog;
use llstarscreamll\TimeClock\Exceptions\AlreadyCheckedInException;
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
     * @var \llstarscreamll\Employees\Contracts\IdentificationRepositoryInterface
     */
    private $identificationRepository;

    /**
     * @var \llstarscreamll\TimeClock\Contracts\TimeClockLogRepositoryInterface
     */
    private $timeClockLogRepository;

    /**
     * @param \llstarscreamll\Employees\Contracts\IdentificationRepositoryInterface $identificationRepository
     * @param \llstarscreamll\TimeClock\Contracts\TimeClockLogRepositoryInterface   $timeClockLogRepository
     */
    public function __construct(
        TimeClockLogRepositoryInterface $timeClockLogRepository,
        IdentificationRepositoryInterface $identificationRepository
    ) {
        $this->timeClockLogRepository = $timeClockLogRepository;
        $this->identificationRepository = $identificationRepository;
    }

    /**
     * @param User   $registrar
     * @param string $identificationCode
     * @param int    $workShiftId
     */
    public function run(User $registrar, string $identificationCode, int $workShiftId = null): TimeClockLog
    {
        $identification = $this->identificationRepository
            ->with(['employee.workShifts'])
            ->findByField('code', $identificationCode, ['id', 'employee_id'])
            ->first();

        $lastCheckIn = $this->timeClockLogRepository->lastCheckInWithOutCheckOutFromUserId(
            $identification->employee_id,
            ['id', 'checked_in_at']
        );

        if ($lastCheckIn) {
            throw new AlreadyCheckedInException('Ya se registra una entrada.', $lastCheckIn->checked_in_at);
        }

        $workShifts = $identification
            ->employee
            ->getWorkShiftsByClosestStartRangeTime(now());

        if ($workShiftId) {
            $workShifts = $workShifts->where('id', $workShiftId);
        }

        if ($workShifts->count() > 1) {
            throw new CanNotDeductWorkShiftException('No fue posible deducir el turno.', $workShifts);
        }

        $timeClockLog = [
            'employee_id' => $identification->employee_id,
            'checked_in_at' => now(),
            'checked_in_by_id' => $registrar->id,
            'work_shift_id' => optional($workShifts->first())->id,
        ];

        return $this->timeClockLogRepository->create($timeClockLog);
    }
}
