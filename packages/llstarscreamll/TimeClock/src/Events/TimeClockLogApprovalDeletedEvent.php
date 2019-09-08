<?php

namespace llstarscreamll\TimeClock\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Class TimeClockLogApprovalDeletedEvent.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class TimeClockLogApprovalDeletedEvent
{
    use Dispatchable;

    /**
     * @var int
     */
    public $timeClockLogId;

    /**
     * @var int
     */
    public $approverId;

    /**
     * Create a new event instance.
     *
     * @param int $timeClockLogId
     * @param int $approverId
     */
    public function __construct(int $timeClockLogId, int $approverId)
    {
        $this->timeClockLogId = $timeClockLogId;
        $this->approverId = $approverId;
    }
}
