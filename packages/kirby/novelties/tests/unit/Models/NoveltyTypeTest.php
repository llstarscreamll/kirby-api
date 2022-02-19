<?php

namespace Kirby\Novelties\Tests\Models;

use Carbon\Carbon;
use Kirby\Company\Contracts\HolidayRepositoryInterface;
use Kirby\Novelties\Enums\DayType;
use Kirby\Novelties\Models\NoveltyType;

/**
 * Class NoveltyTypeTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 *
 * @internal
 */
class NoveltyTypeTest extends \Tests\TestCase
{
    /**
     * @test
     * @dataProvider holidayNoveltyTypes
     *
     * @param  mixed  $attrs
     * @param  mixed  $holidayRepoResult
     * @param  mixed  $relativeDate
     * @param  mixed  $expected
     */
    public function shouldReturnMinStartTimeSlotForHolidayNoveltyType($attrs, $holidayRepoResult, $relativeDate, $expected)
    {
        $this->setupHolidayRepositoryMock($holidayRepoResult);

        $noveltyType = factory(NoveltyType::class)->make($attrs);
        $result = $noveltyType->minStartTimeSlot($relativeDate);

        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     * @dataProvider workdayNoveltyTypes
     *
     * @param  mixed  $attrs
     * @param  mixed  $holidayRepoResult
     * @param  mixed  $relativeDate
     * @param  mixed  $expected
     */
    public function shouldReturnMinStartTimeSlotForWorkdayNoveltyType($attrs, $holidayRepoResult, $relativeDate, $expected)
    {
        $this->setupHolidayRepositoryMock($holidayRepoResult);

        $noveltyType = factory(NoveltyType::class)->make($attrs);
        $result = $noveltyType->minStartTimeSlot($relativeDate);

        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     * @dataProvider holidayNoveltyTypes
     *
     * @param  mixed  $attrs
     * @param  mixed  $holidayRepoResult
     * @param  mixed  $relativeDate
     * @param  mixed  $_
     * @param  mixed  $expected
     */
    public function shouldReturnMaxEndTimeSlotForHoliDayNoveltyType($attrs, $holidayRepoResult, $relativeDate, $_, $expected)
    {
        $this->setupHolidayRepositoryMock($holidayRepoResult);

        /** @var NoveltyType */
        $noveltyType = factory(NoveltyType::class)->make($attrs);
        $result = $noveltyType->maxEndTimeSlot($relativeDate);

        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     * @dataProvider workdayNoveltyTypes
     *
     * @param  mixed  $attrs
     * @param  mixed  $holidayRepoResult
     * @param  mixed  $relativeDate
     * @param  mixed  $_
     * @param  mixed  $expected
     */
    public function shouldReturnMaxEndTimeSlotForWorkDayNoveltyType($attrs, $holidayRepoResult, $relativeDate, $_, $expected)
    {
        $this->setupHolidayRepositoryMock($holidayRepoResult);

        $noveltyType = factory(NoveltyType::class)->make($attrs);
        $result = $noveltyType->maxEndTimeSlot($relativeDate);

        $this->assertEquals($expected, $result);
    }

    public function timeSlotMapping(): array
    {
        return [
            [
                [
                    'apply_on_days_of_type' => DayType::Workday,
                    'apply_on_time_slots' => [
                        ['start' => '06:00:00', 'end' => '21:00:00'],
                    ],
                ],
                Carbon::make('2020-04-01 08:00:00'),
                // assert
                [
                    ['end' => Carbon::make('2020-04-01 08:00:00')],
                ],
            ],
        ];
    }

    public function holidayNoveltyTypes(): array
    {
        return [
            [
                [
                    'apply_on_days_of_type' => DayType::Holiday,
                    'time_zone' => 'America/Bogota',
                    'apply_on_time_slots' => [
                        ['start' => '21:00:00', 'end' => '06:00:00'],
                    ],
                ],
                0,
                Carbon::now()->setTimezone('America/Bogota')->setDateTime(2021, 04, 04, 22, 00, 00), // sunday holiday
                // asserts
                Carbon::make('2021-04-05 02:00:00'), // 21:00 in America/Bogota
                Carbon::make('2021-04-05 04:59:59'), // 23:59 in America/Bogota
            ],
            [
                [
                    'apply_on_days_of_type' => DayType::Holiday,
                    'time_zone' => 'America/Bogota',
                    'apply_on_time_slots' => [
                        ['start' => '21:00:00', 'end' => '06:00:00'],
                    ],
                ],
                0,
                Carbon::make('2020-04-06 06:00:00'), // 01am in America/Bogota workday monday
                // asserts
                Carbon::make('2020-04-06 02:00:00'), // 21:00 in America/Bogota
                Carbon::make('2020-04-06 04:59:59'), // 23:59 in America/Bogota
            ],
            [
                [
                    'apply_on_days_of_type' => DayType::Holiday,
                    'time_zone' => 'America/Bogota',
                    'apply_on_time_slots' => [
                        ['start' => '21:00:00', 'end' => '06:00:00'],
                    ],
                ],
                0,
                Carbon::now()->setTimezone('America/Bogota')->setDateTime(2020, 04, 05, 22, 00, 00), // workday monday
                // asserts
                Carbon::make('2020-04-06 02:00:00'), // 21:00 in America/Bogota
                Carbon::make('2020-04-06 04:59:59'), // 23:59 in America/Bogota
            ],
            [
                [
                    'apply_on_days_of_type' => DayType::Holiday,
                    'apply_on_time_slots' => [
                        ['start' => '21:00:00', 'end' => '06:00:00'],
                    ],
                ],
                0,
                Carbon::make('2020-04-06 06:00:00'), // workday monday
                // asserts
                Carbon::make('2020-04-05 21:00:00'),
                Carbon::make('2020-04-05 23:59:59'),
            ],
            [
                [
                    'apply_on_days_of_type' => DayType::Holiday,
                    'apply_on_time_slots' => [
                        ['start' => '21:00:00', 'end' => '06:00:00'],
                    ],
                ],
                0,
                Carbon::make('2020-04-01 08:00:00'), // workday wednesday
                // asserts
                null,
                null,
            ],
            [
                [
                    'apply_on_days_of_type' => DayType::Holiday,
                    'apply_on_time_slots' => [
                        ['start' => '21:00:00', 'end' => '06:00:00'],
                    ],
                ],
                0,
                Carbon::make('2020-04-04 08:00:00'), // workday saturday
                // asserts
                null,
                null,
            ],
            [
                [
                    'apply_on_days_of_type' => DayType::Holiday,
                    'apply_on_time_slots' => [
                        ['start' => '21:00:00', 'end' => '06:00:00'],
                    ],
                ],
                0,
                Carbon::make('2020-04-05 08:00:00'), // holiday sunday, hours out of range
                // asserts
                null,
                null,
            ],
            [
                [
                    'apply_on_days_of_type' => DayType::Holiday,
                    'apply_on_time_slots' => [
                        ['start' => '21:00:00', 'end' => '06:00:00'],
                    ],
                ],
                0,
                Carbon::make('2020-04-05 22:00:00'), // holiday sunday
                // asserts
                Carbon::make('2020-04-05 21:00:00'),
                Carbon::make('2020-04-05 23:59:59'), // monday is not holiday
            ],
        ];
    }

    public function workdayNoveltyTypes(): array
    {
        return [
            [
                [
                    'apply_on_days_of_type' => DayType::Workday,
                    'time_zone' => 'America/Bogota',
                    'apply_on_time_slots' => [
                        ['start' => '21:00:00', 'end' => '06:00:00'],
                    ],
                ],
                0,
                Carbon::now()->setTimezone('America/Bogota')->setDateTime(2021, 04, 04, 22, 00, 00), // sunday holiday
                // asserts
                Carbon::make('2021-04-05 05:00:00'), // 00:00 in America/Bogota
                Carbon::make('2021-04-05 11:00:00'), // 06:00 in America/Bogota
            ],
            [
                [
                    'apply_on_days_of_type' => DayType::Workday,
                    'apply_on_time_slots' => [
                        ['start' => '21:00:00', 'end' => '06:00:00'],
                    ],
                ],
                0,
                Carbon::make('2019-04-01 22:00:00'), // workday monday
                // asserts
                Carbon::make('2019-04-01 21:00:00'),
                Carbon::make('2019-04-02 06:00:00'),
            ],
            [
                [
                    'apply_on_days_of_type' => DayType::Workday,
                    'apply_on_time_slots' => [
                        ['start' => '21:00:00', 'end' => '06:00:00'],
                    ],
                ],
                0,
                Carbon::make('2020-04-01 22:00:00'), // workday wednesday
                // asserts
                Carbon::make('2020-04-01 21:00:00'),
                Carbon::make('2020-04-02 06:00:00'),
            ],
            [
                [
                    'apply_on_days_of_type' => DayType::Workday,
                    'apply_on_time_slots' => [
                        ['start' => '21:00:00', 'end' => '06:00:00'],
                    ],
                ],
                0,
                Carbon::make('2020-04-06 06:00:00'), // workday monday
                // asserts
                Carbon::make('2020-04-06 00:00:00'), // not 2020-04-05 21:00:00 because is sunday holiday
                Carbon::make('2020-04-06 06:00:00'),
            ],
            [
                [
                    'apply_on_days_of_type' => DayType::Workday,
                    'apply_on_time_slots' => [
                        ['start' => '21:00:00', 'end' => '06:00:00'],
                    ],
                ],
                0,
                Carbon::make('2020-04-01 06:00:00'), // workday wednesday
                // asserts
                Carbon::make('2020-03-31 21:00:00'),
                Carbon::make('2020-04-01 06:00:00'),
            ],
            [
                [
                    'apply_on_days_of_type' => DayType::Workday,
                    'apply_on_time_slots' => [
                        ['start' => '06:00:00', 'end' => '21:00:00'],
                    ],
                ],
                0,
                Carbon::make('2020-04-04 08:00:00'), // workday saturday
                // asserts
                Carbon::make('2020-04-04 06:00:00'),
                Carbon::make('2020-04-04 21:00:00'),
            ],
            [
                [
                    'apply_on_days_of_type' => DayType::Workday,
                    'apply_on_time_slots' => [
                        ['start' => '06:00:00', 'end' => '21:00:00'],
                    ],
                ],
                0,
                Carbon::make('2020-04-05 08:00:00'), // holiday sunday
                // asserts
                null,
                null,
            ],
        ];
    }

    private function setupHolidayRepositoryMock(int $result)
    {
        $this->mock(HolidayRepositoryInterface::class)
            ->shouldReceive('countWhereIn')
            ->andReturn($result);
    }
}
