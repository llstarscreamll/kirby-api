<?php

namespace llstarscreamll\Novelties\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use llstarscreamll\Novelties\Listeners\CreateTimeClockLogNoveltiesApprovalListener;
use llstarscreamll\Novelties\Listeners\DeleteTimeClockLogNoveltiesApprovalListener;
use llstarscreamll\Novelties\Listeners\RegisterTimeClockNoveltiesListener;
use llstarscreamll\TimeClock\Events\CheckedOutEvent;
use llstarscreamll\TimeClock\Events\TimeClockLogApprovalCreatedEvent;
use llstarscreamll\TimeClock\Events\TimeClockLogApprovalDeletedEvent;

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
