<?php

namespace llstarscreamll\TimeClock\Actions;

use llstarscreamll\Users\Models\User;
use llstarscreamll\TimeClock\Models\TimeClockLog;
use llstarscreamll\TimeClock\Exceptions\MissingCheckInException;
use llstarscreamll\TimeClock\Exceptions\TooLateToCheckException;
use llstarscreamll\Novelties\Contracts\NoveltyTypeRepositoryInterface;
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
     * @param  User                    $registrar
     * @param  string                  $identificationCode
     * @throws MissingCheckInException if there is no check in found to log the check out action
     */
    public function run(User $registrar, string $identificationCode): TimeClockLog
    {
        $identification = $this->identificationRepository
            ->findByField('code', $identificationCode, ['id', 'employee_id'])
            ->first();

        $lastCheckIn = $this->timeClockLogRepository->lastCheckInWithOutCheckOutFromEmployeeId(
            $identification->employee_id,
            ['id', 'work_shift_id', 'checked_in_at']
        );

        if (!$lastCheckIn) {
            throw new MissingCheckInException('No se ha registrado entrada.');
        }

        if ($lastCheckIn->workShift && !$lastCheckIn->workShift->isOnTimeToEnd()) {
            $noveltyTypes = $this->noveltyTypeRepository->findForTimeSubtraction();
            throw new TooLateToCheckException('Es tarde para registrar la salida.', $noveltyTypes);
        }

        $timeClockLogUpdate = [
            'checked_out_at' => now(),
            'checked_out_by_id' => $registrar->id,
        ];

        return $this->timeClockLogRepository->update($timeClockLogUpdate, $lastCheckIn->id);
    }
}
