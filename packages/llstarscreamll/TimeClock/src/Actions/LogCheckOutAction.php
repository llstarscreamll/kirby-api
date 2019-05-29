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
     * @param  \llstarscreamll\Users\Models\User                            $registrar
     * @param  string                                                       $identificationCode
     * @throws \llstarscreamll\TimeClock\Exceptions\MissingCheckInException if there is no check in found to log the check out action
     */
    public function run(User $registrar, string $identificationCode): TimeClockLog
    {
        $identification = $this->identificationRepository
            ->findByField('code', $identificationCode, ['id', 'employee_id'])
            ->first();

        $lastCheckIn = $this->timeClockLogRepository->lastCheckInWithOutCheckOutFromEmployeeId(
            $identification->employee_id,
            ['id', 'checked_in_at']
        );

        if (! $lastCheckIn) {
            throw new MissingCheckInException('No se ha registrado entrada.');
        }

        $timeClockLogUpdate = [
            'checked_out_at' => now(),
            'checked_out_by_id' => $registrar->id,
        ];

        return $this->timeClockLogRepository->update($timeClockLogUpdate, $lastCheckIn->id);
    }
}
