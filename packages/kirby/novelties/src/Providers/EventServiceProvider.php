<?php

namespace Kirby\Novelties\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Kirby\Novelties\Listeners\CreateTimeClockLogNoveltiesApprovalListener;
use Kirby\Novelties\Listeners\DeleteTimeClockLogNoveltiesApprovalListener;
use Kirby\Novelties\Listeners\RegisterTimeClockNoveltiesListener;
use Kirby\TimeClock\Events\CheckedOutEvent;
use Kirby\TimeClock\Events\TimeClockLogApprovalCreatedEvent;
use Kirby\TimeClock\Events\TimeClockLogApprovalDeletedEvent;

/**
 * Class EventServiceProvider.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        CheckedOutEvent::class => [
            RegisterTimeClockNoveltiesListener::class,
        ],
        TimeClockLogApprovalCreatedEvent::class => [
            CreateTimeClockLogNoveltiesApprovalListener::class,
        ],
        TimeClockLogApprovalDeletedEvent::class => [
            DeleteTimeClockLogNoveltiesApprovalListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }
}
