<?php

namespace Kirby\Novelties\Tests\Listeners;

use Kirby\Novelties\Actions\RegisterTimeClockNoveltiesAction;
use Kirby\Novelties\Tests\IntegrationTester;
use Kirby\TimeClock\Events\CheckedOutEvent;
use Mockery;

/**
 * Class RegisterTimeClockNoveltiesListenerCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class RegisterTimeClockNoveltiesListenerCest
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
        $timeClockId = 10;
        $actionMock = Mockery::mock(RegisterTimeClockNoveltiesAction::class)
            ->shouldReceive('run')
            ->with($timeClockId)
            ->once()
            ->getMock();

        $I->getApplication()->instance(RegisterTimeClockNoveltiesAction::class, $actionMock);

        event(new CheckedOutEvent($timeClockId));
    }
}
