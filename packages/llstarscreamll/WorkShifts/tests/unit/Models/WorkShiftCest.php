<?php

namespace WorkShifts\Models;

use Carbon\Carbon;
use Codeception\Example;
use WorkShifts\UnitTester;
use llstarscreamll\WorkShifts\Models\WorkShift;

/**
 * Class WorkShiftCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class WorkShiftCest
{
    /**
     * @return array
     */
    protected function punctualityProvider(): array
    {
        return [
            [
                'slot' => 'end',
                'workShiftData' => [
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
                    'time_slots' => [
                        ['start' => '07:00', 'end' => '18:00'],
                    ],
                ],
                'testNow' => Carbon::setTestNow(Carbon::create(2019, 04, 01, 06, 00)),
                'time' => now(),
                'expected' => -1, // too eager
            ],
            [
                'slot' => 'start',
                'workShiftData' => [
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
                    'time_slots' => [
                        ['start' => '07:00', 'end' => '12:00'],
                        ['start' => '14:00', 'end' => '18:00'],
                    ],
                ],
                'testNow' => Carbon::setTestNow(Carbon::create(2019, 04, 01, 14, 00)),
                'time' => now(),
                'expected' => 1, // too late
            ],
            [
                'slot' => 'end',
                'workShiftData' => [
                    'time_slots' => [
                        ['start' => '07:00', 'end' => '12:00'],
                        ['start' => '14:00', 'end' => '18:00'],
                    ],
                ],
                'testNow' => Carbon::setTestNow(Carbon::create(2019, 04, 01, 15, 00)), // middle between 12:00 and 18:00
                'time' => now(),
                'expected' => -1, // too early
            ],
            [
                'slot' => 'end',
                'workShiftData' => [
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
     * @dataProvider punctualityProvider
     * @param UnitTester $I
     */
    public function testSlotPunctuality(UnitTester $I, Example $data)
    {
        $workShift = new WorkShift($data['workShiftData']);

        $result = $workShift->slotPunctuality($data['slot'], $data['time']);

        $I->assertEquals($data['expected'], $result);
    }
}
