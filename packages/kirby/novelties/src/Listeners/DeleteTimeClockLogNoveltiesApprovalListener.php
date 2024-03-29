<?php

namespace Kirby\Novelties\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Kirby\Novelties\Contracts\NoveltyRepositoryInterface;
use Kirby\TimeClock\Events\TimeClockLogApprovalDeletedEvent;

/**
 * Class DeleteTimeClockLogNoveltiesApprovalListener.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class DeleteTimeClockLogNoveltiesApprovalListener implements ShouldQueue
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
    public function handle(TimeClockLogApprovalDeletedEvent $event)
    {
        $noveltiesIds = $this->noveltyRepository->findWhere(['time_clock_log_id' => $event->timeClockLogId], ['id']);
        $this->noveltyRepository->deleteApprovals($noveltiesIds->pluck('id')->all(), $event->approverId);
    }
}
