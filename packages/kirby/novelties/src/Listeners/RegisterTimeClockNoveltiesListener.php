<?php

namespace Kirby\Novelties\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Kirby\Novelties\Actions\RegisterTimeClockNoveltiesAction;
use Kirby\TimeClock\Events\CheckedOutEvent;

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
     * @param  RegisterTimeClockNoveltiesAction  $registerTimeClockNoveltiesAction
     */
    public function __construct(RegisterTimeClockNoveltiesAction $registerTimeClockNoveltiesAction)
    {
        $this->registerTimeClockNoveltiesAction = $registerTimeClockNoveltiesAction;
    }

    /**
     * Handle the event.
     *
     * @param  CheckedOutEvent  $event
     * @return void
     */
    public function handle(CheckedOutEvent $event)
    {
        $this->registerTimeClockNoveltiesAction->run($event->timeClockLogId);
    }
}
