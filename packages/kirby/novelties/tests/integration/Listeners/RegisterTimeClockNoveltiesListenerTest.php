<?php

namespace Kirby\Novelties\Tests\Listeners;

use Kirby\Novelties\Actions\RegisterTimeClockNoveltiesAction;
use Kirby\TimeClock\Events\CheckedOutEvent;
use Mockery;

/**
 * Class RegisterTimeClockNoveltiesListenerTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class RegisterTimeClockNoveltiesListenerTest extends \Tests\TestCase
{
    /**
     * @test
     */
    public function shouldBeCalledOnEventDispatchAndInvokeRunMethodOnActionClass()
    {
        $timeClockId = 10;
        $actionMock = Mockery::mock(RegisterTimeClockNoveltiesAction::class)
            ->shouldReceive('run')
            ->with($timeClockId)
            ->once()
            ->getMock();

        $this->instance(RegisterTimeClockNoveltiesAction::class, $actionMock);

        event(new CheckedOutEvent($timeClockId));
    }
}
