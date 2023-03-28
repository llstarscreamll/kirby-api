<?php

namespace Kirby\WorkShifts\Tests\unit\Models;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Kirby\WorkShifts\Models\WorkShift;

/**
 * Class WorkShiftTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 *
 * @internal
 */
class WorkShiftTest extends \Tests\TestCase
{
    public function punctualityDataProvider(): array
    {
        return [
            [
                'slot' => 'end',
                'workShiftData' => [
                    'name' => 'test',
                    'time_slots' => [
                        ['start' => '07:00', 'end' => '18:00'],
                    ],
                ],
                'testNow' => Carbon::setTestNow(Carbon::create(2019, 04, 01, 18, 00)),
                'time' => now(),
                'expected' => 0, // on time
            ],
            [
                'slot' => 'start',
                'workShiftData' => [
                    'name' => 'test',
                    'time_slots' => [
                        ['start' => '07:00', 'end' => '18:00'],
                    ],
                ],
                'testNow' => Carbon::setTestNow(Carbon::create(2019, 04, 01, 07, 00)),
                'time' => now(),
                'expected' => 0, // on time
            ],
            [
                'slot' => 'start',
                'workShiftData' => [
                    'name' => 'test',
                    'time_slots' => [
                        ['start' => '07:00', 'end' => '18:00'],
                    ],
                ],
                'testNow' => Carbon::setTestNow(Carbon::create(2019, 04, 01, 06, 00)),
                'time' => now(),
                'expected' => -1, // too early
            ],
            [
                'slot' => 'start',
                'workShiftData' => [
                    'name' => 'test',
                    'time_slots' => [
                        ['start' => '07:00', 'end' => '18:00'],
                    ],
                ],
                'testNow' => Carbon::setTestNow(Carbon::create(2019, 04, 01, 8, 00)),
                'time' => now(),
                'expected' => 1, // too late
            ],
            [
                'slot' => 'end',
                'workShiftData' => [
                    'name' => 'test',
                    'time_slots' => [
                        ['start' => '07:00', 'end' => '18:00'],
                    ],
                ],
                'testNow' => Carbon::setTestNow(Carbon::create(2019, 04, 01, 16, 00)),
                'time' => now(),
                'expected' => -1, // too early
            ],
            [
                'slot' => 'end',
                'workShiftData' => [
                    'name' => 'test',
                    'time_slots' => [
                        ['start' => '07:00', 'end' => '18:00'],
                    ],
                ],
                'testNow' => Carbon::setTestNow(Carbon::create(2019, 04, 01, 20, 00)),
                'time' => now(),
                'expected' => 1, // too late
            ],
            [
                'slot' => 'end',
                'workShiftData' => [
                    'name' => 'test',
                    'time_slots' => [
                        ['start' => '07:00', 'end' => '12:00'],
                        ['start' => '14:00', 'end' => '18:00'],
                    ],
                ],
                'testNow' => Carbon::setTestNow(Carbon::create(2019, 04, 01, 18, 00)),
                'time' => now(),
                'expected' => 0, // on time
            ],
            [
                'slot' => 'end',
                'workShiftData' => [
                    'name' => 'test',
                    'time_slots' => [
                        ['start' => '07:00', 'end' => '12:00'],
                        ['start' => '14:00', 'end' => '18:00'],
                    ],
                ],
                'testNow' => Carbon::setTestNow(Carbon::create(2019, 04, 01, 14, 00)),
                'time' => now(),
                'expected' => 1, // too late relative to 7am-12m
            ],
            [
                'slot' => 'end',
                'workShiftData' => [
                    'name' => 'test',
                    'time_slots' => [
                        ['start' => '07:00', 'end' => '12:00'],
                        ['start' => '14:00', 'end' => '18:00'],
                    ],
                ],
                'testNow' => Carbon::setTestNow(Carbon::create(2019, 04, 01, 15, 00)),
                'time' => now(),
                'expected' => -1, // too early relative to 14 to 18
            ],
            [
                'slot' => 'end',
                'workShiftData' => [
                    'name' => 'test',
                    'time_slots' => [
                        ['start' => '07:00', 'end' => '12:00'],
                        ['start' => '14:00', 'end' => '18:00'],
                    ],
                ],
                'testNow' => Carbon::setTestNow(Carbon::create(2019, 04, 01, 17, 00)),
                'time' => now(),
                'expected' => -1, // too early
            ],
        ];
    }

    /**
     * @test
     *
     * @dataProvider punctualityDataProvider
     *
     * @param  mixed  $slot
     * @param  mixed  $workShiftData
     * @param  mixed  $_
     * @param  mixed  $time
     * @param  mixed  $expected
     */
    public function testSlotPunctuality($slot, $workShiftData, $_, $time, $expected)
    {
        $workShift = factory(WorkShift::class)->make($workShiftData);

        $result = $workShift->slotPunctuality($slot, $time);

        $this->assertEquals($expected, $result);
    }

    public function deadTimeDataProvider(): array
    {
        return [
            [
                'workShiftData' => [
                    'name' => 'test',
                    'time_slots' => null,
                ],
                'relativeToTime' => null,
                'expected' => [],
            ],
            [
                'workShiftData' => [
                    'name' => 'test',
                    'time_slots' => [
                        ['start' => '07:00', 'end' => '12:00'],
                    ],
                ],
                'relativeToTime' => null,
                'expected' => [],
            ],
            [
                'workShiftData' => [
                    'name' => 'test',
                    'time_slots' => [
                        ['start' => '07:00', 'end' => '12:00'],
                        ['start' => '14:00', 'end' => '18:00'],
                    ],
                ],
                'relativeToTime' => Carbon::parse('2019-04-01'),
                'expected' => [
                    [
                        'start' => Carbon::parse('2019-04-01 12:00'),
                        'end' => Carbon::parse('2019-04-01 14:00'),
                    ],
                ],
            ],
            [
                'workShiftData' => [
                    'name' => 'test',
                    'time_slots' => [
                        ['start' => '07:00', 'end' => '10:00'],
                        ['start' => '11:00', 'end' => '14:00'],
                        ['start' => '15:00', 'end' => '18:00'],
                        ['start' => '19:00', 'end' => '22:00'],
                    ],
                ],
                'relativeToTime' => Carbon::parse('2019-04-01'),
                'expected' => [
                    [
                        'start' => Carbon::parse('2019-04-01 10:00'),
                        'end' => Carbon::parse('2019-04-01 11:00'),
                    ],
                    [
                        'start' => Carbon::parse('2019-04-01 14:00'),
                        'end' => Carbon::parse('2019-04-01 15:00'),
                    ],
                    [
                        'start' => Carbon::parse('2019-04-01 18:00'),
                        'end' => Carbon::parse('2019-04-01 19:00'),
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     *
     * @dataProvider deadTimeDataProvider
     *
     * @param  mixed  $workShiftData
     * @param  mixed  $relativeToTime
     * @param  mixed  $expected
     */
    public function deadTimeRanges($workShiftData, $relativeToTime, $expected)
    {
        // @var WorkShift
        $workShift = factory(WorkShift::class)->make($workShiftData);

        $result = $workShift->deadTimeRanges($relativeToTime);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(count($expected), $result);
        $result->each(function ($slotResult, $key) use ($expected) {
            $this->assertTrue($slotResult['start']->equalTo($expected[$key]['start']));
            $this->assertTrue($slotResult['end']->equalTo($expected[$key]['end']));
        });
    }

    public function workShiftExamples(): array
    {
        return [
            [
                [
                    'name' => 'test',
                    'time_zone' => 'America/Bogota',
                    'time_slots' => [
                        ['start' => '07:00', 'end' => '12:00'],
                        ['start' => '13:00', 'end' => '18:00'],
                    ],
                ],
                Carbon::now()->setTimezone('UTC')->setDate(2020, 06, 21)->setTime(06, 00, 00),
                // assert
                [
                    [Carbon::make('2020-06-21 12:00:00'), Carbon::make('2020-06-21 17:00:00')],
                    [Carbon::make('2020-06-21 18:00:00'), Carbon::make('2020-06-21 23:00:00')],
                ],
            ],
        ];
    }

    /**
     * @test
     *
     * @dataProvider workShiftExamples
     *
     * @param  mixed  $attrs
     * @param  mixed  $relativeToTime
     * @param  mixed  $expected
     */
    public function mappedTimeSlots($attrs, $relativeToTime, $expected)
    {
        $workShift = factory(WorkShift::class)->make($attrs);
        $result = $workShift->mappedTimeSlots($relativeToTime);

        $this->assertEquals($expected, $result->all());
    }
}
