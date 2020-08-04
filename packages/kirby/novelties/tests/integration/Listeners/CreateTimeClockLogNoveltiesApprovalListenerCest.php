<?php

namespace Kirby\Novelties\Tests\Listeners;

use Illuminate\Support\Collection;
use Kirby\Novelties\Contracts\NoveltyRepositoryInterface;
use Kirby\Novelties\Tests\IntegrationTester;
use Kirby\TimeClock\Events\TimeClockLogApprovalCreatedEvent;
use Mockery;

/**
 * Class CreateTimeClockLogNoveltiesApprovalListenerCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateTimeClockLogNoveltiesApprovalListenerCest
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
            ->shouldReceive('setApprovals')
            ->with([1, 2], $approverId)
            ->once()
            ->andReturn(true)
            ->getMock();

        $I->getApplication()->instance(NoveltyRepositoryInterface::class, $repositoryMock);

        event(new TimeClockLogApprovalCreatedEvent($timeClockLogId, $approverId));
    }
}
