<?php
namespace llstarscreamll\TimeClock\Actions;

use llstarscreamll\Users\Models\User;
use llstarscreamll\TimeClock\Models\TimeClockLog;
use llstarscreamll\TimeClock\Exceptions\MissingCheckInException;
use llstarscreamll\TimeClock\Contracts\TimeClockLogRepositoryInterface;
use llstarscreamll\Employees\Contracts\IdentificationRepositoryInterface;

/**
 * Class LogCheckOutAction.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class LogCheckOutAction
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
     * @param  User                                                         $registrar
     * @param  string                                                       $identificationCode
     * @throws \llstarscreamll\TimeClock\Exceptions\MissingCheckInException if there is no check in found to log the check out action
     */
    public function run(User $registrar, string $identificationCode): TimeClockLog
    {
        $identification = $this->identificationRepository
            ->with(['employee.workShifts'])
            ->findByField('code', $identificationCode, ['id', 'employee_id'])
            ->first();

        $lastTimeClockCheckIn = $this->timeClockLogRepository->lastCheckInWithOutCheckOutFromUserId(
            $identification->employee_id,
            ['id', 'checked_in_at']
        );

        if (!$lastTimeClockCheckIn) {
            throw new MissingCheckInException();
        }

        $workShift = $identification->employee->getWorkShiftsByClosestRangeTime($lastTimeClockCheckIn->checked_in_at, now());

        $timeClockLogUpdate = [
            'work_shift_id' => optional($workShift)->id,
            'checked_out_at' => now(),
            'checked_out_by_id' => $registrar->id,
        ];

        return $this->timeClockLogRepository->update($timeClockLogUpdate, $lastTimeClockCheckIn->id);
    }
}
