<?php

namespace llstarscreamll\TimeClock\Actions;

use llstarscreamll\Users\Models\User;
use llstarscreamll\TimeClock\Models\TimeClockLog;
use llstarscreamll\TimeClock\Exceptions\AlreadyCheckedInException;
use llstarscreamll\TimeClock\Contracts\TimeClockLogRepositoryInterface;
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
     * @param IdentificationRepositoryInterface $identificationRepository
     * @param TimeClockLogRepositoryInterface   $timeClockLogRepository
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
     */
    public function run(User $registrar, string $identificationCode): TimeClockLog
    {
        $identification = $this->identificationRepository
                               ->with(['employee.workShifts'])
                               ->findByField('code', $identificationCode, ['id', 'employee_id'])
                               ->first();

        $lastTimeClockCheckIn = $this->timeClockLogRepository->lastCheckInWithOutCheckOutFromUserId(
            $identification->employee_id, ['id', 'checked_in_at']
        );

        if ($lastTimeClockCheckIn) {
            throw new AlreadyCheckedInException("El usuario ya registró entrada en {$lastTimeClockCheckIn->checked_in_at}");
        }

        $timeClockLog = [
            'employee_id' => $identification->employee_id,
            'checked_in_at' => now(),
            'checked_in_by_id' => $registrar->id,
        ];

        return $this->timeClockLogRepository->create($timeClockLog);
    }
}
