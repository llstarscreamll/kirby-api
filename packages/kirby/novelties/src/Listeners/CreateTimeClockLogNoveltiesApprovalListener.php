<?php

namespace Kirby\Novelties\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Kirby\Novelties\Contracts\NoveltyRepositoryInterface;
use Kirby\TimeClock\Events\TimeClockLogApprovalCreatedEvent;

/**
 * Class CreateTimeClockLogNoveltiesApprovalListener.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateTimeClockLogNoveltiesApprovalListener implements ShouldQueue
{
    /**
     * @var NoveltyRepositoryInterface
     */
    private $noveltyRepository;

    public function __construct(NoveltyRepositoryInterface $noveltyRepository)
    {
        $this->noveltyRepository = $noveltyRepository;
    }

    /**
     * Handle the event.
     */
    public function handle(TimeClockLogApprovalCreatedEvent $event)
    {
        $noveltiesIds = $this->noveltyRepository->findWhere(['time_clock_log_id' => $event->timeClockLogId], ['id']);
        $this->noveltyRepository->setApprovals($noveltiesIds->pluck('id')->all(), $event->approverId);
    }
}
