<?php

namespace llstarscreamll\TimeClock\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Class CheckedOutEvent.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CheckedOutEvent
{
    use Dispatchable;

    /**
     * @var int
     */
    public $timeClockLogId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(int $timeClockLogId)
    {
        $this->timeClockLogId = $timeClockLogId;
    }
}
