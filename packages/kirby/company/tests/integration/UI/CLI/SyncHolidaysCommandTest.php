<?php

namespace Kirby\Company\Tests\integration\UI\CLI;

use Kirby\Company\Contracts\HolidaysServiceInterface;

/**
 * Class SyncHolidaysCommandTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 *
 * @internal
 */
class SyncHolidaysCommandTest extends \Tests\TestCase
{
    /**
     * @test
     */
    public function testToSyncCurrentYearHolidays()
    {
        $holidayDate = now()->year.'-02-11';

        // existing 'country' holiday 'date', after command execution, holiday should be updated, not duplicated
        $this->haveRecord('holidays', [
            'country_code' => 'co',
            'name' => 'some holiday name',
            'description' => 'some example description',
            'date' => $holidayDate,
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        $this->mock(HolidaysServiceInterface::class)
            ->shouldReceive('get')
            ->once()
            ->with('co', now()->year)
            ->andReturn([[
                'name' => 'holiday name',
                'description' => 'example description',
                'date' => $holidayDate,
            ]])->getMock();

        $this->artisan('company:sync-holidays');

        // said holidays musts be present only one times on DB
        $this->assertDatabaseRecordsCount(1, 'holidays', ['country_code' => 'co', 'date' => $holidayDate]);

        $this->assertDatabaseHas('holidays', [
            'country_code' => 'co',
            'name' => 'holiday name',
            'description' => 'example description',
            'date' => $holidayDate,
        ]);
    }

    /**
     * @test
     */
    public function testSyncNextYearHolidays()
    {
        $year = now()->addYear()->year;
        $this->mock(HolidaysServiceInterface::class)
            ->shouldReceive('get')->once()->with('co', $year)->andReturn([])
            ->getMock();

        $this->artisan('company:sync-holidays', ['--next-year' => true]);
    }
}
