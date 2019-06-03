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
    protected function isOnTimeToEndFooProvider(): array
    {
        return [
            [
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
     * @dataProvider isOnTimeToEndFooProvider
     * @param UnitTester $I
     */
    public function testIsOnTimeToEndFoo(UnitTester $I, Example $data)
    {
        $workShift = new WorkShift($data['workShiftData']);

        $result = $workShift->isOnTimeToEnd($data['time']);

        $I->assertEquals($data['expected'], $result);
    }
}
