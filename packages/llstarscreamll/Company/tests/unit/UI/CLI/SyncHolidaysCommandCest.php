<?php

namespace Company\UI\CLI;

use Company\UnitTester;
use llstarscreamll\Company\Contracts\HolidaysServiceInterface;

/**
 * Class SyncHolidaysCommandCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SyncHolidaysCommandCest
{
    /**
     * @param UnitTester $I
     */
    public function _before(UnitTester $I)
    {
    }

    /**
     * @param UnitTester $I
     */
    public function _after(UnitTester $I)
    {
        \Mockery::close();
    }

    /**
     * @test
     * @param UnitTester $I
     */
    public function testToSyncCurrentYearHolidays(UnitTester $I)
    {
        $holidayDate = now()->year . '-02-11';
        $serviceMock = \Mockery::mock(HolidaysServiceInterface::class)
            ->shouldReceive('get')
            ->once()
            ->with('co', now()->year)
            ->andReturn([[
                'name' => 'holiday name',
                'description' => 'example description',
                'date' => $holidayDate,
            ]])->getMock();

        $I->getApplication()->instance(HolidaysServiceInterface::class, $serviceMock);

        $I->callArtisan('company:sync-holidays');

        $I->seeRecord('holidays', [
            'country_code' => 'co',
            'name' => 'holiday name',
            'description' => 'example description',
            'date' => $holidayDate,
        ]);
    }

    /**
     * @test
     * @param UnitTester $I
     */
    public function testSyncNextYearHolidays(UnitTester $I)
    {
        $year = now()->addYear()->year;
        $serviceMock = \Mockery::mock(HolidaysServiceInterface::class)
            ->shouldReceive('get')->once()->with('co', $year)->andReturn([])
            ->getMock();

        $I->getApplication()->instance(HolidaysServiceInterface::class, $serviceMock);

        $I->callArtisan('company:sync-holidays', ['--next-year' => true]);
    }
}
