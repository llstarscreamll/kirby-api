<?php

namespace Kirby\Novelties\Tests\Listeners;

use Illuminate\Support\Collection;
use Kirby\Novelties\Contracts\NoveltyRepositoryInterface;
use Kirby\Novelties\Tests\IntegrationTester;
use Kirby\TimeClock\Events\TimeClockLogApprovalDeletedEvent;
use Mockery;

/**
 * Class DeleteTimeClockLogNoveltiesApprovalListenerTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class DeleteTimeClockLogNoveltiesApprovalListenerTest extends \Tests\TestCase
{
    
    

    
    

    /**
     * @test

     */
    public function shouldBeCalledOnEventDispatchAndInvokeRunMethodOnActionClass()
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

        $this->instance(NoveltyRepositoryInterface::class, $repositoryMock);

        event(new TimeClockLogApprovalDeletedEvent($timeClockLogId, $approverId));
    }
}
