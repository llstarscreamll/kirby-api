<?php

namespace Novelties\Actions;

use Mockery;
use Codeception\Example;
use Illuminate\Support\Arr;
use Novelties\IntegrationTester;
use Illuminate\Support\Collection;
use llstarscreamll\Company\Models\Holiday;
use llstarscreamll\WorkShifts\Models\WorkShift;
use llstarscreamll\Novelties\Models\NoveltyType;
use llstarscreamll\TimeClock\Models\TimeClockLog;
use llstarscreamll\Novelties\Actions\RegisterTimeClockNoveltiesAction;

/**
 * Class RegisterTimeClockNoveltiesActionCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class RegisterTimeClockNoveltiesActionCest
{
    /**
     * @var Collection
     */
    private $workShifts;

    /**
     * @var Collection
     */
    private $noveltyTypes;

    /**
     * @param IntegrationTester $I
     */
    public function _before(IntegrationTester $I)
    {
        $this->noveltyTypes = NoveltyType::all();
        $this->workShifts = new Collection();

        // holiday test
        Holiday::create([
            'country_code' => 'CO',
            'name' => 'Test holiday',
            'description' => 'test holiday description',
            'date' => '2019-07-01',
        ]);

        $this->workShifts->push(factory(WorkShift::class)->create([
            'name' => '7-18',
            'meal_time_in_minutes' => 60, // 1 hour
            'min_minutes_required_to_discount_meal_time' => 60 * 11, // 11 hours
            'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
            'time_slots' => [['start' => '07:00', 'end' => '18:00']], // should check in at 7am
        ]));

        $this->workShifts->push(factory(WorkShift::class)->create([
            'name' => '7-17',
            'meal_time_in_minutes' => 60, // 1 hour
            'min_minutes_required_to_discount_meal_time' => 60 * 11, // 11 hours
            'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
            'time_slots' => [
                ['start' => '07:00', 'end' => '12:30'],
                ['start' => '13:30', 'end' => '17:00'],
            ],
        ]));

        $this->workShifts->push(factory(WorkShift::class)->create([
            'name' => '22-6',
            'meal_time_in_minutes' => 0,
            'min_minutes_required_to_discount_meal_time' => 0,
            'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
            'time_slots' => [
                ['start' => '22:00', 'end' => '06:00'],
            ],
        ]));

        $this->workShifts->push(factory(WorkShift::class)->create([
            'name' => '14-22',
            'meal_time_in_minutes' => 0,
            'min_minutes_required_to_discount_meal_time' => 0,
            'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
            'time_slots' => [
                ['start' => '14:00', 'end' => '22:00'],
            ],
        ]));

        $this->workShifts->push(factory(WorkShift::class)->create([
            'name' => '6-14',
            'meal_time_in_minutes' => 0,
            'min_minutes_required_to_discount_meal_time' => 0,
            'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
            'time_slots' => [
                ['start' => '06:00', 'end' => '14:00'],
            ],
        ]));
    }

    /**
     * @param IntegrationTester $I
     */
    public function _after(IntegrationTester $I)
    {
        Mockery::close();
    }

    /**
     * @test
     */
    protected function successCases()
    {
        return [
            [
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'checked_in_at' => '2019-04-01 07:00:00', // on time
                    'checked_out_at' => '2019-04-01 18:00:00', // on time
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => 60 * 10, // 11 work hours - 1 hour launch
                    ],
                ],
            ],
            [
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'check_in_novelty_type_code' => 'HEDI', // extra daytime
                    'checked_in_at' => '2019-04-02 06:00:00', // 1 hours early
                    'checked_out_at' => '2019-04-02 18:00:00', // on time
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => 60 * 10, // 12 work hours - 1 hour launch - 1 early
                    ],
                    [
                        'novelty_type_code' => 'HEDI', // extra daytime
                        'total_time_in_minutes' => 60 * 1, // 1 hour early
                    ],
                ],
            ],
            [
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'check_in_novelty_type_code' => 'HEDI', // extra daytime
                    'check_out_novelty_type_code' => 'HEDI', // extra daytime
                    'checked_in_at' => '2019-04-03 06:00:00', // 1 hour early
                    'checked_out_at' => '2019-04-03 19:00:00', // 1 hour late
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => 60 * 10, // 12 work hours - 1 hour launch - 1 early
                    ],
                    [
                        'novelty_type_code' => 'HEDI', // extra daytime
                        'total_time_in_minutes' => 60 * 2, // 1 hour early + 1 hour late
                    ],
                ],
            ],
            [
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'check_in_novelty_type_code' => 'HEDI', // extra daytime
                    'check_out_novelty_type_code' => 'HADI', // additional time
                    'checked_in_at' => '2019-04-03 06:00:00', // 1 hour early
                    'checked_out_at' => '2019-04-03 19:00:00', // 1 hour late
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => 60 * 10, // 12 work hours - 1 hour launch - 1 early
                    ],
                    [
                        'novelty_type_code' => 'HEDI', // extra daytime
                        'total_time_in_minutes' => 60 * 1, // 1 hour early
                    ],
                    [
                        'novelty_type_code' => 'HADI', // additional time
                        'total_time_in_minutes' => 60 * 1, // 1 hour late
                    ],
                ],
            ],
            [
                'timeClockLog' => [
                    // without work shift
                    'work_shift_name' => null,
                    'check_in_novelty_type_code' => 'HADI', // additional time
                    'checked_in_at' => '2019-03-31 08:00:00',
                    'checked_out_at' => '2019-03-31 14:00:00',
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HADI',
                        'total_time_in_minutes' => 60 * 6, // 6 hours
                    ],
                ],
            ],
            [
                'timeClockLog' => [
                    'work_shift_name' => '7-17',
                    'checked_in_at' => '2019-04-01 07:00:00', // on time
                    'checked_out_at' => '2019-04-01 12:30:00', // on time
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => (60 * 5) + 30, // 5.5 hours from 7am to 12:30pm
                    ],
                ],
            ],
            [
                'timeClockLog' => [
                    'work_shift_name' => '7-17',
                    'checked_in_at' => '2019-04-01 13:30:00', // on time
                    'checked_out_at' => '2019-04-01 17:00:00', // on time
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => (60 * 3) + 30, // 3.5 hours from 12:30pm to 5pm
                    ],
                ],
            ],
            [
                'timeClockLog' => [
                    'work_shift_name' => '7-17',
                    'check_out_novelty_type_code' => 'HEDI', // additional time
                    'checked_in_at' => '2019-04-01 13:30:00', // on time
                    'checked_out_at' => '2019-04-01 19:00:00', // 2 hours late
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => (60 * 3) + 30, // 3.5 hours from 12:30pm to 5pm
                    ],
                    [
                        'novelty_type_code' => 'HEDI',
                        'total_time_in_minutes' => (60 * 2), // 2 hours from 5pm to 7pm
                    ],
                ],
            ],
            [
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'check_in_novelty_type_code' => 'PP', // personal permission
                    'checked_in_at' => '2019-04-01 08:00:00', // 1 hour late
                    'checked_out_at' => '2019-04-01 18:00:00', // on time
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        // 10 hours (from 8am to 6pm), minimum minutes to subtract launch time not reached
                        'total_time_in_minutes' => 60 * 10,
                    ],
                    [
                        'novelty_type_code' => 'PP',
                        'total_time_in_minutes' => 60 * -1, // 1 hour from 7am to 8am
                    ],
                ],
            ],
            [
                'timeClockLog' => [
                    'work_shift_name' => '7-17',
                    'check_in_novelty_type_code' => 'PP', // personal permission
                    'checked_in_at' => '2019-04-01 08:00:00', // 1 hour late
                    'checked_out_at' => '2019-04-01 12:30:00', // on time
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        // 4.5 hours (from 8am to 12:30pm)
                        'total_time_in_minutes' => (60 * 4) + 30,
                    ],
                    [
                        'novelty_type_code' => 'PP',
                        'total_time_in_minutes' => 60 * -1, // 1 hour from 7am to 8am
                    ],
                ],
            ],
            [
                'timeClockLog' => [
                    'work_shift_name' => '7-17',
                    'check_out_novelty_type_code' => 'PP', // personal permission
                    'checked_in_at' => '2019-04-01 07:00:00', // on time
                    'checked_out_at' => '2019-04-01 11:30:00', // 1 hour early
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        // 4.5 hours (from 8am to 12:30pm)
                        'total_time_in_minutes' => (60 * 4) + 30,
                    ],
                    [
                        'novelty_type_code' => 'PP',
                        'total_time_in_minutes' => 60 * -1, // 1 hour from 11:30am to 12:30pm
                    ],
                ],
            ],
            [
                'timeClockLog' => [ // time clock log without work shift
                    'check_in_novelty_type_code' => 'HADI', // additional time
                    'checked_in_at' => '2019-04-01 07:00:00', // time doesn't matters because work shift is null
                    'checked_out_at' => '2019-04-01 14:00:00', // time doesn't matters because work shift is null
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HADI',
                        'total_time_in_minutes' => (60 * 7), // 7 hours
                    ],
                ],
            ],
            [
                'timeClockLog' => [ // time clock log with night work shift
                    'work_shift_name' => '22-6',
                    'checked_in_at' => '2019-04-01 22:00:00', // on time
                    'checked_out_at' => '2019-04-02 06:00:00', // on time
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'RECNO',
                        'total_time_in_minutes' => (60 * 8), // 8 hours
                    ],
                ],
            ],
            [
                'timeClockLog' => [ // time clock log with night work shift
                    'work_shift_name' => '22-6',
                    'checked_in_at' => '2019-06-30 22:00:00', // sunday holiday, on time
                    'checked_out_at' => '2019-07-01 06:00:00', // test monday holiday, on time
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HNF',
                        'total_time_in_minutes' => (60 * 8), // 8 hours
                    ],
                ],
            ],
            [
                'timeClockLog' => [ // time clock log with night work shift and one holiday
                    'work_shift_name' => '22-6',
                    'checked_in_at' => '2019-03-30 22:00:00', // saturday, on time
                    'checked_out_at' => '2019-03-31 06:00:00', // sunday holiday, on time
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'RECNO',
                        'total_time_in_minutes' => (60 * 2), // 2 hours, from 2019-03-30 22:00:00 to 23:59:59
                    ],
                    [
                        'novelty_type_code' => 'HNF',
                        'total_time_in_minutes' => (60 * 6), // 6 hours, from 2019-03-31 00:00 to 06:00:00
                    ],
                ],
            ],
            [
                'timeClockLog' => [ // time clock log with one holiday and night work shift
                    'work_shift_name' => '22-6',
                    'checked_in_at' => '2019-07-01 22:00:00', // monday holiday, on time
                    'checked_out_at' => '2019-07-02 06:00:00', // tuesday work day, on time
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'RECNO',
                        'total_time_in_minutes' => (60 * 6), // 6 hours, from 2019-07-02 00:00 to 06:00
                    ],
                    [
                        'novelty_type_code' => 'HNF',
                        'total_time_in_minutes' => (60 * 2), // 2 hours, from 2019-07-01 22:00 to 23:59:59
                    ],
                ],
            ],
            [
                'timeClockLog' => [ // time clock log on work day
                    'work_shift_name' => '14-22',
                    'checked_in_at' => '2019-04-01 14:00:00', // monday work day, on time
                    'checked_out_at' => '2019-04-01 22:00:00', // monday work day, on time
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'RECNO',
                        'total_time_in_minutes' => (60 * 1), // 1 hour, from 21:00:00 to 22:00
                    ],
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => (60 * 7), // 7 hours, from 14:00 to 21:00:00
                    ],
                ],
            ],
            [
                'timeClockLog' => [ // time clock log on holiday
                    'work_shift_name' => '14-22',
                    'checked_in_at' => '2019-07-01 14:00:00', // monday holiday, on time
                    'checked_out_at' => '2019-07-01 22:00:00', // monday holiday, on time
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HNF',
                        'total_time_in_minutes' => (60 * 1), // 1 hour, from 21:00:00 to 22:00
                    ],
                    [
                        'novelty_type_code' => 'HDF',
                        'total_time_in_minutes' => (60 * 7), // 7 hours, from 14:00 to 21:00:00
                    ],
                ],
            ],
            [
                'timeClockLog' => [ // time clock log on workday
                    'work_shift_name' => '6-14',
                    'check_in_novelty_type_code' => 'HADI',
                    'checked_in_at' => '2019-04-01 05:00:00', // workday, one hour early
                    'checked_out_at' => '2019-04-01 14:00:00', // workday, on time
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => (60 * 8), // 8 hours, from 06:00:00 to 14:00
                    ],
                    [
                        'novelty_type_code' => 'HADI',
                        'total_time_in_minutes' => (60 * 1), // 1 hour, from 05:00 to 06:00:00
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider successCases
     * @param IntegrationTester $I
     */
    public function whenWorkShiftCheckInAndCheckOutIsOnTime(IntegrationTester $I, Example $data)
    {
        $timeClockData = $this->mapTimeClockData($data['timeClockLog']);
        $timeClockLog = factory(TimeClockLog::class)->create($timeClockData);

        $action = app(RegisterTimeClockNoveltiesAction::class);

        $I->assertTrue($action->run($timeClockLog->id));

        // only one novelty should be created
        $createdRecordsCount = $I->grabNumRecords('novelties', [
            'time_clock_log_id' => $timeClockLog->id,
            'employee_id' => optional($timeClockLog->employee)->id,
        ]);

        $I->assertEquals(count($data['createdNovelties']), $createdRecordsCount);

        foreach ($data['createdNovelties'] as $novelty) {
            $noveltyType = $this->noveltyTypes->firstWhere('code', $novelty['novelty_type_code']);

            $I->seeRecord('novelties', [
                'time_clock_log_id' => $timeClockLog->id,
                'employee_id' => $timeClockLog->employee->id,
                'novelty_type_id' => $noveltyType->id,
                'total_time_in_minutes' => $novelty['total_time_in_minutes'],
            ]);
        }
    }

    /**
     * Map time clock provider data to be used on Laravel factory.
     *
     * @param  array   $timeClock
     * @return array
     */
    private function mapTimeClockData(array $timeClock): array
    {
        $keysToRemove = [
            'check_in_novelty_type_code',
            'check_out_novelty_type_code',
            'work_shift_name',
        ];

        if (isset($timeClock['check_in_novelty_type_code'])) {
            $checkInNoveltyType = $this->noveltyTypes->firstWhere('code', $timeClock['check_in_novelty_type_code']);
            $timeClock['check_in_novelty_type_id'] = optional($checkInNoveltyType)->id;
        }

        if (isset($timeClock['check_out_novelty_type_code'])) {
            $checkInNoveltyType = $this->noveltyTypes->firstWhere('code', $timeClock['check_out_novelty_type_code']);
            $timeClock['check_out_novelty_type_id'] = optional($checkInNoveltyType)->id;
        }

        if (isset($timeClock['work_shift_name'])) {
            $workShift = $this->workShifts->firstWhere('name', $timeClock['work_shift_name']);
            $timeClock['work_shift_id'] = optional($workShift)->id;
        }

        return Arr::except($timeClock, $keysToRemove);
    }
}
