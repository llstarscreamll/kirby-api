<?php

namespace Novelties\Listeners;

use Mockery;
use Novelties\IntegrationTester;
use Illuminate\Support\Collection;
use llstarscreamll\Novelties\Contracts\NoveltyRepositoryInterface;
use llstarscreamll\TimeClock\Events\TimeClockLogApprovalDeletedEvent;

/**
 * Class DeleteTimeClockLogNoveltiesApprovalListenerCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class DeleteTimeClockLogNoveltiesApprovalListenerCest
{
    /**
     * @param IntegrationTester $I
     */
    public function _before(IntegrationTester $I)
    {
        //
    }

    /**
     * @param IntegrationTester $I
     */
    public function _after(IntegrationTester $I)
    {
        Mockery::close();
    }

    /**
     * @test
     * @param IntegrationTester $I
     */
    public function shouldBeCalledOnEventDispatchAndInvokeRunMethodOnActionClass(IntegrationTester $I)
    {
        $timeClockLogId = 10;
        $approverId = 15;
        $repositoryMock = Mockery::mock(NoveltyRepositoryInterface::class)
            ->shouldReceive('findWhere')
            ->with(['time_clock_log_id' => $timeClockLogId], ['id'])
            ->once()
            ->andReturn(new Collection([['id' => 1], ['id' => 2]]))
            ->shouldReceive('deleteApprovals')
            ->with([1, 2], $approverId)
            ->once()
            ->andReturn(true)
            ->getMock();

        $I->getApplication()->instance(NoveltyRepositoryInterface::class, $repositoryMock);

        event(new TimeClockLogApprovalDeletedEvent($timeClockLogId, $approverId));
    }
}
