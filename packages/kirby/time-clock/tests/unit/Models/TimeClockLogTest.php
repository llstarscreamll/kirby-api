<?php

namespace Kirby\TimeClock\Tests\unit\Models;

use Carbon\Carbon;
use Kirby\Company\Contracts\HolidayRepositoryInterface;
use Kirby\Novelties\Enums\DayType;
use Kirby\TimeClock\Models\TimeClockLog;

/**
 * Class TimeClockLogTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 *
 * @internal
 */
class TimeClockLogTest extends \Tests\TestCase
{
    /**
     * @return array
     */
    public function timeClockLogs()
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
     *
     * @param mixed $checkIn
     * @param mixed $checkOut
     * @param mixed $expected
     */
    public function getClockedTimeMinutesByDayType($checkIn, $checkOut, $expected)
    {
        $checkInDate = $checkIn['at'];
        $checkOutDate = $checkOut['at'];

        $this->mock(HolidayRepositoryInterface::class)
            ->shouldReceive('countWhereIn')
            ->with('date', [$checkInDate->toDateString()])
            ->andReturn((int) $checkIn['is_holiday'])
            ->shouldReceive('countWhereIn')
            ->with('date', [$checkOutDate->toDateString()])
            ->andReturn((int) $checkOut['is_holiday']);

        $timeClockLog = factory(TimeClockLog::class)->make();
        $timeClockLog->checked_in_at = $checkInDate;
        $timeClockLog->checked_out_at = $checkOutDate;

        $holidayResult = $timeClockLog->getClockedTimeMinutesByDayType(DayType::Holiday());
        $workdayResult = $timeClockLog->getClockedTimeMinutesByDayType(DayType::Workday());

        $this->assertEquals($expected['holiday_minutes'], $holidayResult[0], 'holiday minutes');
        $this->assertEquals($expected['workday_minutes'], $workdayResult[0], 'workday minutes');
        $this->assertEquals($expected['holiday_time_range'], array_map(fn ($date) => (string) $date, $holidayResult[1]));
        $this->assertEquals($expected['workday_time_range'], array_map(fn ($date) => (string) $date, $workdayResult[1]));
    }
}
