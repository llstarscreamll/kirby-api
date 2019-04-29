<?php

namespace llstarscreamll\TimeClock\Actions;

use llstarscreamll\TimeClock\Contracts\TimeClockLogRepositoryInterface;
use llstarscreamll\TimeClock\Exceptions\AlreadyCheckedInException;
use llstarscreamll\TimeClock\Models\TimeClockLog;
use llstarscreamll\Users\Contracts\IdentificationRepositoryInterface;
use llstarscreamll\Users\Models\User;

/**
 * Class LogCheckInAction.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class LogCheckInAction
{
    /**
     * @var \llstarscreamll\Users\Contracts\IdentificationRepositoryInterface
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
                               ->with(['user.workShifts'])
                               ->findByField('code', $identificationCode, ['id', 'user_id'])
                               ->first();

        $lastTimeClockCheckIn = $this->timeClockLogRepository->lastCheckInWithOutCheckOutFromUserId(
            $identification->user_id, ['id', 'checked_in_at']
        );

        if ($lastTimeClockCheckIn) {
            throw new AlreadyCheckedInException("El usuario ya registró entrada en {$lastTimeClockCheckIn->checked_in_at}");
        }

        $timeClockLog = [
            'employee_id' => $identification->user_id,
            'checked_in_at' => now(),
            'checked_in_by_id' => $registrar->id,
        ];

        return $this->timeClockLogRepository->create($timeClockLog);
    }
}
