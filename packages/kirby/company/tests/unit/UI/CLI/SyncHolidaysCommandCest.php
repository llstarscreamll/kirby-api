<?php

namespace Company\UI\CLI;

use Company\UnitTester;
use Kirby\Company\Contracts\HolidaysServiceInterface;

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
        $holidayDate = now()->year.'-02-11';

        // existing 'country' holiday 'date', after command execution, holiday should be updated, not duplicated
        $I->haveRecord('holidays', [
            'country_code' => 'co',
            'name' => 'some holiday name',
            'description' => 'some example description',
            'date' => $holidayDate,
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

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

        // said holidays musts be present only one times on DB
        $I->seeNumRecords(1, 'holidays', ['country_code' => 'co', 'date' => $holidayDate]);

        $I->seeRecord('holidays', [
            'country_code' => 'co',
            'name' => 'holiday name',
            'description' => 'example description',
            'date' => $holidayDate,
        ]);

        $holiday = $I->grabRecord('holidays', [
            'country_code' => 'co',
            'date' => $holidayDate,
        ]);

        $I->assertNotNull($holiday['created_at']);
        $I->assertNotNull($holiday['updated_at']);
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
