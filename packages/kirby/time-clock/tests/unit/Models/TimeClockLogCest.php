<?php

namespace ClockTime;

use Carbon\Carbon;
use Codeception\Example;
use Kirby\Company\Contracts\HolidayRepositoryInterface;
use Kirby\Novelties\Enums\DayType;
use Kirby\TimeClock\Models\TimeClockLog;
use Mockery;

/**
 * Class TimeClockLogCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class TimeClockLogCest
{
    /**
     * @param UnitTester $I
     */
    public function _before(UnitTester $I)
    {
    }

    /**
     * @return array
     */
    protected function timeClockLogs()
    {
        return [
            [ // full holiday
                'check_in' => ['at' => Carbon::parse('2019-01-01 07:00:00'), 'is_holiday' => true],
                'check_out' => ['at' => Carbon::parse('2019-01-01 18:00:00'), 'is_holiday' => true],
                'expected' => [
                    'holiday_minutes' => 11 * 60,
                    'workday_minutes' => 0,
                    'holiday_time_range' => ['2019-01-01 07:00:00', '2019-01-01 18:00:00'],
                    'workday_time_range' => [],
                ],
            ],
            [ // full workday
                'check_in' => ['at' => Carbon::parse('2018-12-31 07:00:00'), 'is_holiday' => false],
                'check_out' => ['at' => Carbon::parse('2018-12-31 18:00:00'), 'is_holiday' => false],
                'expected' => [
                    'holiday_minutes' => 0,
                    'workday_minutes' => 11 * 60,
                    'holiday_time_range' => [],
                    'workday_time_range' => ['2018-12-31 07:00:00', '2018-12-31 18:00:00'],
                ],
            ],
            [ // half workday and half holiday
                'check_in' => ['at' => Carbon::parse('2018-12-31 22:00:00'), 'is_holiday' => false],
                'check_out' => ['at' => Carbon::parse('2019-01-01 06:00:00'), 'is_holiday' => true],
                'expected' => [
                    'holiday_minutes' => (6 * 60),
                    'workday_minutes' => (2 * 60),
                    'holiday_time_range' => ['2019-01-01 00:00:00', '2019-01-01 06:00:00'],
                    'workday_time_range' => ['2018-12-31 22:00:00', '2018-12-31 23:59:59'],
                ],
            ],
            [ // half holiday and half workday
                'check_in' => ['at' => Carbon::parse('2019-01-01 22:00:00'), 'is_holiday' => true],
                'check_out' => ['at' => Carbon::parse('2019-01-02 06:00:00'), 'is_holiday' => false],
                'expected' => [
                    'holiday_minutes' => (2 * 60),
                    'workday_minutes' => (6 * 60),
                    'holiday_time_range' => ['2019-01-01 22:00:00', '2019-01-01 23:59:59'],
                    'workday_time_range' => ['2019-01-02 00:00:00', '2019-01-02 06:00:00'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider timeClockLogs
     * @test
     * @param UnitTester $I
     */
    public function getClockedTimeMinutesByDayType(UnitTester $I, Example $data)
    {
        $checkInDate = $data['check_in']['at'];
        $checkOutDate = $data['check_out']['at'];

        $holidaysRepositoryMock = Mockery::mock(HolidayRepositoryInterface::class)
            ->shouldReceive('countWhereIn')
            ->with('date', [$checkInDate->toDateString()])
            ->andReturn((int) $data['check_in']['is_holiday'])
            ->shouldReceive('countWhereIn')
            ->with('date', [$checkOutDate->toDateString()])
            ->andReturn((int) $data['check_out']['is_holiday'])
            ->getMock();

        $I->getApplication()->instance(HolidayRepositoryInterface::class, $holidaysRepositoryMock);

        $timeClockLog = factory(TimeClockLog::class)->make();
        $timeClockLog->checked_in_at = $checkInDate;
        $timeClockLog->checked_out_at = $checkOutDate;

        $holidayResult = $timeClockLog->getClockedTimeMinutesByDayType(DayType::Holiday());
        $workdayResult = $timeClockLog->getClockedTimeMinutesByDayType(DayType::Workday());

        $I->assertEquals($data['expected']['holiday_minutes'], $holidayResult[0], 'holiday minutes');
        $I->assertEquals($data['expected']['workday_minutes'], $workdayResult[0], 'workday minutes');
        $I->assertEquals($data['expected']['holiday_time_range'], array_map(fn ($date) => (string) $date, $holidayResult[1]));
        $I->assertEquals($data['expected']['workday_time_range'], array_map(fn ($date) => (string) $date, $workdayResult[1]));
    }
}
