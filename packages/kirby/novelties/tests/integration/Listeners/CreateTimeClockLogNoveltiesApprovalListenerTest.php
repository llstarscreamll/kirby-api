<?php

namespace Kirby\Novelties\Tests\Listeners;

use Illuminate\Support\Collection;
use Kirby\Novelties\Contracts\NoveltyRepositoryInterface;
use Kirby\TimeClock\Events\TimeClockLogApprovalCreatedEvent;
use Mockery;

/**
 * Class CreateTimeClockLogNoveltiesApprovalListenerTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 *
 * @internal
 */
class CreateTimeClockLogNoveltiesApprovalListenerTest extends \Tests\TestCase
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
            ->shouldReceive('setApprovals')
            ->with([1, 2], $approverId)
            ->once()
            ->andReturn(true)
            ->getMock();

        $this->instance(NoveltyRepositoryInterface::class, $repositoryMock);

        event(new TimeClockLogApprovalCreatedEvent($timeClockLogId, $approverId));
    }
}
