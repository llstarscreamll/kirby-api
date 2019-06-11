<?php

namespace llstarscreamll\Novelties\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use llstarscreamll\TimeClock\Events\CheckedOutEvent;
use llstarscreamll\Novelties\Actions\RegisterTimeClockNoveltiesAction;

/**
 * Class RegisterTimeClockNoveltiesListener.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class RegisterTimeClockNoveltiesListener implements ShouldQueue
{
    /**
     * @var RegisterTimeClockNoveltiesAction
     */
    private $registerTimeClockNoveltiesAction;

    /**
     * @param RegisterTimeClockNoveltiesAction $registerTimeClockNoveltiesAction
     */
    public function __construct(RegisterTimeClockNoveltiesAction $registerTimeClockNoveltiesAction)
    {
        $this->registerTimeClockNoveltiesAction = $registerTimeClockNoveltiesAction;
    }

    /**
     * Handle the event.
     *
     * @param  CheckedOutEvent $event
     * @return void
     */
    public function handle(CheckedOutEvent $event)
    {
        $this->registerTimeClockNoveltiesAction->run($event->timeClockLogId);
    }
}
