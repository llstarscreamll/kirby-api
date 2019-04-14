<?php
namespace llstarscreamll\TimeClock\Actions;

use llstarscreamll\TimeClock\Contracts\TimeClockLogRepositoryInterface;
use llstarscreamll\TimeClock\Exceptions\MissingCheckInException;
use llstarscreamll\TimeClock\Models\TimeClockLog;
use llstarscreamll\Users\Contracts\IdentificationRepositoryInterface;
use llstarscreamll\Users\Models\User;

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

        $timeClockLogUpdate = [
            'checked_out_at' => now(),
            'checked_out_by_id' => $registrar->id,
        ];

        $timeClockLog = $this->timeClockLogRepository->lastCheckInFromUserId($identification->user_id, ['id']);

        if (!$timeClockLog) {
            throw new MissingCheckInException();
        }

        return $this->timeClockLogRepository->update($timeClockLogUpdate, $timeClockLog->id);
    }
}
