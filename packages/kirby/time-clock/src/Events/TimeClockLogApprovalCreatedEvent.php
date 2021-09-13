<?php

namespace Kirby\TimeClock\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Class TimeClockLogApprovalCreatedEvent.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class TimeClockLogApprovalCreatedEvent
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
     * @param  int  $timeClockLogId
     * @param  int  $approverId
     */
    public function __construct(int $timeClockLogId, int $approverId)
    {
        $this->timeClockLogId = $timeClockLogId;
        $this->approverId = $approverId;
    }
}
