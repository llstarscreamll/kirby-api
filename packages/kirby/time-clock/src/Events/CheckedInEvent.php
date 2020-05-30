<?php

namespace Kirby\TimeClock\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Class CheckedInEvent.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CheckedInEvent
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
