<?php

namespace Novelties\Listeners;

use Mockery;
use Novelties\IntegrationTester;
use llstarscreamll\TimeClock\Models\TimeClockLog;
use llstarscreamll\TimeClock\Events\CheckedOutEvent;
use llstarscreamll\Novelties\Listeners\RegisterTimeClockNoveltiesListener;

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
    public function _before(IntegrationTester $I) {}

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
    public function shouldBeTriggeredOnCheckedOutEventDispatch(IntegrationTester $I)
    {
        $listenerMock = Mockery::mock(RegisterTimeClockNoveltiesListener::class)
            ->shouldReceive('handle')
            ->once()
            ->getMock();

        $I->getApplication()->instance(RegisterTimeClockNoveltiesListener::class, $listenerMock);
        $timeClockLog = factory(TimeClockLog::class)->create();

        event(new CheckedOutEvent($timeClockLog->id));
    }
}
