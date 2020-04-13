<?php
namespace Novelties\Models;

use Carbon\Carbon;
use Codeception\Example;
use Kirby\Company\Contracts\HolidayRepositoryInterface;
use Kirby\Novelties\Enums\DayType;
use Kirby\Novelties\Models\NoveltyType;
use Mockery;
use Novelties\UnitTester;

/**
 * Class NoveltyTypeCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class NoveltyTypeCest
{
    /**
     * @test
     * @dataProvider holidayNoveltyTypes
     * @param UnitTester $I
     */
    public function shouldReturnMinStartTimeSlotForHolidayNoveltyType(UnitTester $I, Example $data)
    {
        [$attrs, $holidayRepoResult, $relativeDate, $expected] = $data;
        $this->setupHolidayRepositoryMock($I, $holidayRepoResult);

        $noveltyType = factory(NoveltyType::class)->make($attrs);
        $result = $noveltyType->minStartTimeSlot($relativeDate);

        $I->assertEquals($expected, $result);
    }

    /**
     * @test
     * @dataProvider workdayNoveltyTypes
     * @param UnitTester $I
     */
    public function shouldReturnMinStartTimeSlotForWorkdayNoveltyType(UnitTester $I, Example $data)
    {
        [$attrs, $holidayRepoResult, $relativeDate, $expected] = $data;
        $this->setupHolidayRepositoryMock($I, $holidayRepoResult);

        $noveltyType = factory(NoveltyType::class)->make($attrs);
        $result = $noveltyType->minStartTimeSlot($relativeDate);

        $I->assertEquals($expected, $result);
    }

    /**
     * @test
     * @dataProvider holidayNoveltyTypes
     * @param UnitTester $I
     */
    public function shouldReturnMaxEndTimeSlotForHoliDayNoveltyType(UnitTester $I, Example $data)
    {
        [$attrs, $holidayRepoResult, $relativeDate, $_, $expected] = $data;
        $this->setupHolidayRepositoryMock($I, $holidayRepoResult);

        /** @var NoveltyType */
        $noveltyType = factory(NoveltyType::class)->make($attrs);
        $result = $noveltyType->maxEndTimeSlot($relativeDate);

        $I->assertEquals($expected, $result);
    }

    /**
     * @test
     * @dataProvider workdayNoveltyTypes
     * @param UnitTester $I
     */
    public function shouldReturnMaxEndTimeSlotForWorkDayNoveltyType(UnitTester $I, Example $data)
    {
        [$attrs, $holidayRepoResult, $relativeDate, $_, $expected] = $data;
        $this->setupHolidayRepositoryMock($I, $holidayRepoResult);

        $noveltyType = factory(NoveltyType::class)->make($attrs);
        $result = $noveltyType->maxEndTimeSlot($relativeDate);

        $I->assertEquals($expected, $result);
    }

    /**
     * @param UnitTester $I
     * @param int $result
     */
    private function setupHolidayRepositoryMock(UnitTester $I, int $result)
    {
        $holidayRepoMock = Mockery::mock(HolidayRepositoryInterface::class)
            ->shouldReceive('countWhereIn')
            ->andReturn($result)
            ->getMock();

        $I->getApplication()->instance(HolidayRepositoryInterface::class, $holidayRepoMock);
    }

    protected function timeSlotMapping(): array
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
                    ['end' => Carbon::make('2020-04-01 08:00:00')]
                ]
            ]
        ];
    }

    protected function holidayNoveltyTypes(): array
    {
        return [
            [
                [
                    'apply_on_days_of_type' => DayType::Holiday,
                    'apply_on_time_slots' => [
                        ['start' => '21:00:00', 'end' => '06:00:00'],
                    ],
                ],
                0,
                Carbon::make('2020-04-06 06:00:00'), // work day monday
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
            ],/**/
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

    protected function workdayNoveltyTypes(): array
    {
        return [
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
                Carbon::make('2020-04-06 06:00:00'), // work day monday
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
}
