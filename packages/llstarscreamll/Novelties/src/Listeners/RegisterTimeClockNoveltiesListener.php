<?php

namespace llstarscreamll\Novelties\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use llstarscreamll\TimeClock\Events\CheckedOutEvent;

/**
 * Class RegisterTimeClockNoveltiesListener.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class RegisterTimeClockNoveltiesListener implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  CheckedOutEvent $event
     * @return void
     */
    public function handle(CheckedOutEvent $event)
    {
        //
    }
}
