<?php

namespace llstarscreamll\Novelties\Actions;

use llstarscreamll\TimeClock\Contracts\TimeClockLogRepositoryInterface;

/**
 * Class RegisterTimeClockNoveltiesAction.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class RegisterTimeClockNoveltiesAction
{
    /**
     * @var TimeClockLogRepositoryInterface
     */
    private $timeClockLogRepository;

    /**
     * @param TimeClockLogRepositoryInterface $timeClockLogRepository
     */
    public function __construct(
        TimeClockLogRepositoryInterface $timeClockLogRepository
    ) {
        $this->timeClockLogRepository = $timeClockLogRepository;
    }

    /**
     * @param int $timeClockLogId
     */
    public function run(int $timeClockLogId)
    {
        echo "Hola";
    }
}
