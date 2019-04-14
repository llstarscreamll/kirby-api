<?php
namespace llstarscreamll\TimeClock\Actions;

use llstarscreamll\TimeClock\Contracts\TimeClockLogRepositoryInterface;
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

        $workShift = $identification->user->getFirstWorkShiftByClosestStartTime(now());

        $timeClockLog = [
            'employee_id' => $identification->user_id,
            'work_shift_id' => optional($workShift)->id,
            'checked_in_at' => now(),
            'checked_in_by_id' => $registrar->id,
        ];

        return $this->timeClockLogRepository->create($timeClockLog);
    }
}
