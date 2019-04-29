<?php

namespace llstarscreamll\TimeClock\Actions;

use llstarscreamll\Users\Models\User;
use llstarscreamll\TimeClock\Models\TimeClockLog;
use llstarscreamll\TimeClock\Exceptions\MissingCheckInException;
use llstarscreamll\Users\Contracts\IdentificationRepositoryInterface;
use llstarscreamll\TimeClock\Contracts\TimeClockLogRepositoryInterface;

/**
 * Class LogCheckOutAction.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class LogCheckOutAction
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
     * @param  User                    $registrar
     * @param  string                  $identificationCode
     * @throws MissingCheckInException if there is no check in found to log the check out action
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

        if (! $lastTimeClockCheckIn) {
            throw new MissingCheckInException();
        }

        $workShift = $identification->user->getFirstWorkShiftByClosestRangeTime($lastTimeClockCheckIn->checked_in_at, now());

        $timeClockLogUpdate = [
            'work_shift_id' => optional($workShift)->id,
            'checked_out_at' => now(),
            'checked_out_by_id' => $registrar->id,
        ];

        return $this->timeClockLogRepository->update($timeClockLogUpdate, $lastTimeClockCheckIn->id);
    }
}
