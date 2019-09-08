<?php

namespace llstarscreamll\Novelties\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use llstarscreamll\Novelties\Contracts\NoveltyRepositoryInterface;
use llstarscreamll\TimeClock\Events\TimeClockLogApprovalDeletedEvent;

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

    /**
     * @param NoveltyRepositoryInterface $noveltyRepository
     */
    public function __construct(NoveltyRepositoryInterface $noveltyRepository)
    {
        $this->noveltyRepository = $noveltyRepository;
    }

    /**
     * Handle the event.
     *
     * @param  TimeClockLogApprovalDeletedEvent $event
     * @return void
     */
    public function handle(TimeClockLogApprovalDeletedEvent $event)
    {
        $noveltiesIds = $this->noveltyRepository->findWhere(['time_clock_log_id' => $event->timeClockLogId], ['id']);
        $this->noveltyRepository->deleteApprovals($noveltiesIds->pluck('id')->all(), $event->approverId);
    }
}
