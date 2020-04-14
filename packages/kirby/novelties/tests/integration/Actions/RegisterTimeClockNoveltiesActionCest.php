<?php

namespace Novelties\Actions;

use Carbon\Carbon;
use Codeception\Example;
use DefaultNoveltyTypesSeed;
use DefaultWorkShiftsSeeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Kirby\Company\Models\Holiday;
use Kirby\Company\Models\SubCostCenter;
use Kirby\Novelties\Actions\RegisterTimeClockNoveltiesAction;
use Kirby\Novelties\Models\Novelty;
use Kirby\Novelties\Models\NoveltyType;
use Kirby\Novelties\Novelties;
use Kirby\TimeClock\Models\TimeClockLog;
use Kirby\WorkShifts\Models\WorkShift;
use Mockery;
use Novelties\IntegrationTester;

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
     * @var Collection
     */
    private $subCostCenters;

    /**
     * @param IntegrationTester $I
     */
    public function _before(IntegrationTester $I)
    {
        // default novelty types from seeds are used on this test suite, so put
        // the time zone as UTC to prevent overhead with time zone differences
        DB::table('novelty_types')->update(['time_zone' => 'UTC']);

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
            'name' => '7-16',
            'meal_time_in_minutes' => 0,
            'min_minutes_required_to_discount_meal_time' => 0,
            'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
            'time_slots' => [
                ['start' => '07:00', 'end' => '12:00'],
                ['start' => '13:00', 'end' => '16:00'],
            ],
        ]));

        $this->workShifts->push(factory(WorkShift::class)->create([
            'name' => '7-18',
            'meal_time_in_minutes' => 60, // 1 hour
            'min_minutes_required_to_discount_meal_time' => 60 * 11, // 11 hours
            'grace_minutes_before_start_times' => 15,
            'grace_minutes_after_start_times' => 15,
            'grace_minutes_before_end_times' => 15,
            'grace_minutes_after_end_times' => 15,
            'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
            'time_slots' => [['start' => '07:00', 'end' => '18:00']], // should check in at 7am
        ]));

        $this->workShifts->push(factory(WorkShift::class)->create([
            'name' => '6-14 America/Bogota',
            'meal_time_in_minutes' => 0,
            'min_minutes_required_to_discount_meal_time' => 0, // 11 hours
            'grace_minutes_before_start_times' => 15,
            'grace_minutes_after_start_times' => 15,
            'grace_minutes_before_end_times' => 15,
            'grace_minutes_after_end_times' => 15,
            'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
            'time_slots' => [['start' => '06:00', 'end' => '14:00']], // should check in at 7am
            'time_zone' => 'America/Bogota', // no UTC time zone!!
        ]));

        $this->workShifts->push(factory(WorkShift::class)->create([
            'name' => '7-12 13:30-17:00',
            'grace_minutes_before_start_times' => 25,
            'grace_minutes_after_end_times' => 20,
            'meal_time_in_minutes' => 60, // 1 hour
            'min_minutes_required_to_discount_meal_time' => 0, // 11 hours
            'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
            'time_slots' => [
                ['start' => '07:00', 'end' => '12:00'],
                ['start' => '13:30', 'end' => '17:00'],
            ],
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
            'name' => '14-22 Sundays',
            'meal_time_in_minutes' => 0,
            'min_minutes_required_to_discount_meal_time' => 0,
            'applies_on_days' => [7], // sundays
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

        $this->subCostCenters = factory(SubCostCenter::class, 2)->create();
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
                    'checked_out_at' => '2019-04-01 16:00:00', // too early, because of scheduled novelty
                    'check_out_novelty_type_code' => 'PP', // novelty for early check out
                    'sub_cost_center_id' => 1,
                ],
                'scheduledNovelties' => [
                    [
                        'novelty_type_code' => 'CM', // scheduled novelty for check out
                        'scheduled_start_at' => '2019-04-01 17:00:00',
                        'scheduled_end_at' => '2019-04-01 18:00:00',
                        'total_time_in_minutes' => 60 * 1, // 1 hour from 5pm to 6pm
                    ],
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        // 9 hours (from 7am to 4pm), minimum minutes to subtract launch time not reached
                        'total_time_in_minutes' => 60 * 9,
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                        'scheduled_start_at' => '2019-04-01 07:00:00',
                        'scheduled_end_at' => '2019-04-01 16:00:00',
                    ],
                    [
                        'novelty_type_code' => 'CM', // this novelty should be now attached to time clock log record
                        'total_time_in_minutes' => 60 * 1, // 1 hour from 5pm to 6pm
                        'scheduled_start_at' => '2019-04-01 17:00:00',
                        'scheduled_end_at' => '2019-04-01 18:00:00',
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                    ],
                    [
                        'novelty_type_code' => 'PP', // novelty for too early check out
                        'total_time_in_minutes' => (60 - 1) * -1, // -1 hour from 16:00 to 17:00
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                        'scheduled_start_at' => '2019-04-01 16:00:01',
                        'scheduled_end_at' => '2019-04-01 16:59:59',
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
                'timeClockLog' => [ // check in/out without work shift on holiday
                    'work_shift_name' => null, // without work shift
                    'check_in_novelty_type_code' => null, // without checkin novelty type
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
                'timeClockLog' => [ // check in/out without work shift on workday
                    'work_shift_name' => null, // without work shift
                    'check_in_novelty_type_code' => null, // without checkin novelty type
                    'checked_in_at' => '2019-01-10 08:00:00',
                    'checked_out_at' => '2019-01-10 14:00:00',
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
                    'work_shift_name' => '7-12 13:30-17:00',
                    'check_in_novelty_type_code' => null,
                    'checked_in_at' => '2019-04-01 06:48:00', // on time fot work shift
                    'checked_out_at' => '2019-04-01 08:00:00', // on time for scheduled novelty
                ],
                'scheduledNovelties' => [
                    [
                        'novelty_type_code' => 'CM', // scheduled novelty for check out
                        'scheduled_start_at' => '2019-04-01 08:00:00',
                        'scheduled_end_at' => '2019-04-01 12:00:00',
                        'total_time_in_minutes' => 60 * 4, // 4 hours from 7am to 12m
                    ],
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'scheduled_start_at' => '2019-04-01 07:00:00',
                        'scheduled_end_at' => '2019-04-01 07:59:59',
                        'total_time_in_minutes' => 59,
                    ],
                    [
                        'novelty_type_code' => 'CM',
                        'scheduled_start_at' => '2019-04-01 08:00:00',
                        'scheduled_end_at' => '2019-04-01 12:00:00',
                        'total_time_in_minutes' => 60 * 4, // from 7am to 12m
                    ],
                ],
            ],
            [
                'timeClockLog' => [
                    'work_shift_name' => '7-16',
                    'check_in_novelty_type_code' => 'HADI',
                    'checked_in_at' => '2019-04-01 06:00:00', // too early
                    'checked_out_at' => '2019-04-01 16:00:00', // on time, with 12m-13pm gap reached
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => 60 * 5,
                        'scheduled_start_at' => '2019-04-01 07:00:00',
                        'scheduled_end_at' => '2019-04-01 12:00:00',
                    ],
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => 60 * 3,
                        'scheduled_start_at' => '2019-04-01 13:00:00',
                        'scheduled_end_at' => '2019-04-01 16:00:00',
                    ],
                    [
                        'novelty_type_code' => 'HADI',
                        'total_time_in_minutes' => (60 * 1) - 1,
                        'scheduled_start_at' => '2019-04-01 06:00:00',
                        'scheduled_end_at' => '2019-04-01 06:59:59',
                    ],
                    [
                        'novelty_type_code' => 'HADI',
                        'total_time_in_minutes' => (60 * 1) - 1,
                        'scheduled_start_at' => '2019-04-01 12:00:01',
                        'scheduled_end_at' => '2019-04-01 12:59:59',
                    ],
                ],
            ],
            [
                'test' => 'too early check in with novelty, too late check out without novelty, work shift with gap and only one check out',
                'timeClockLog' => [
                    'work_shift_name' => '7-16',
                    'check_in_novelty_type_code' => 'HADI',
                    'check_out_novelty_type_code' => null,
                    'checked_in_at' => '2019-04-01 05:00:00', // too early
                    'checked_out_at' => '2019-04-01 18:00:00', // too late, without checkout at 12m
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => 60 * 5, // from 7am to 12m
                        'scheduled_start_at' => '2019-04-01 07:00:00',
                        'scheduled_end_at' => '2019-04-01 12:00:00',
                    ],
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => 60 * 3, // from 13pm to 16m
                        'scheduled_start_at' => '2019-04-01 13:00:00',
                        'scheduled_end_at' => '2019-04-01 16:00:00',
                    ],
                    [
                        'novelty_type_code' => 'HADI',
                        'total_time_in_minutes' => (60 * 2) - 1, // from 05:00 to 06:59
                        'scheduled_start_at' => '2019-04-01 05:00:00',
                        'scheduled_end_at' => '2019-04-01 06:59:59',
                    ],
                    [
                        'novelty_type_code' => 'HADI',
                        'total_time_in_minutes' => (60 * 1) - 1, // 7-16 shift has a gap from 12:01pm to 12:59pm
                        'scheduled_start_at' => '2019-04-01 12:00:01',
                        'scheduled_end_at' => '2019-04-01 12:59:59',
                    ],
                    [
                        'novelty_type_code' => 'HADI', // without check out novelty, then should be HADI time by default
                        'total_time_in_minutes' => (60 * 2) - 1, // from 16:01pm to 18:00pm
                        'scheduled_start_at' => '2019-04-01 16:00:01',
                        'scheduled_end_at' => '2019-04-01 18:00:00',
                    ],
                ],
            ],
            [
                'test' => 'on time to work shift with one gap but only one check out at the end of second time slot',
                'timeClockLog' => [
                    'work_shift_name' => '7-16',
                    'checked_in_at' => '2019-04-01 07:00:00', // on time
                    'checked_out_at' => '2019-04-01 16:00:00', // on time, without checkout at 12m
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => 60 * 5, // from 7am to 12m
                        'scheduled_start_at' => '2019-04-01 07:00:00',
                        'scheduled_end_at' => '2019-04-01 12:00:00',
                    ],
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => 60 * 3, // from 13pm to 16m
                        'scheduled_start_at' => '2019-04-01 13:00:00',
                        'scheduled_end_at' => '2019-04-01 16:00:00',
                    ],
                    [
                        'novelty_type_code' => 'HADI',
                        'total_time_in_minutes' => (60 * 1) - 1, // 7-16 shift has a gap from 12:01pm to 12:59pm
                        'scheduled_start_at' => '2019-04-01 12:00:01',
                        'scheduled_end_at' => '2019-04-01 12:59:59',
                    ],
                ],
            ],
            [
                'test' => 'too late check, closest to the end of first part of work shift',
                'timeClockLog' => [
                    'work_shift_name' => '7-12 13:30-17:00',
                    'checked_in_at' => '2019-04-01 11:49:00', // too late to first shift time slot
                    'checked_out_at' => '2019-04-01 12:15:00', // on time to first shift time slot, with grace time
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => 11,
                        'scheduled_start_at' => '2019-04-01 11:49:00',
                        'scheduled_end_at' => '2019-04-01 12:00:00', // rounded to work shift first slot end
                    ],
                    [
                        'novelty_type_code' => 'PP',
                        'total_time_in_minutes' => ((60 * 5) - 12) * -1,
                        'scheduled_start_at' => '2019-04-01 07:00:00',
                        'scheduled_end_at' => '2019-04-01 11:48:59',
                    ],
                ],
            ],
            [
                'test' => 'soft limits touched on shift without gaps and meal time',
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'checked_in_at' => '2019-04-01 06:55:00', // on time, with grace time
                    'checked_out_at' => '2019-04-01 17:50:00', // on time, with grace time
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => (60 * 5) - 1,
                        'scheduled_start_at' => '2019-04-01 07:00:00',
                        'scheduled_end_at' => '2019-04-01 11:59:59',
                    ],
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => (60 * 5) - 1,
                        'scheduled_start_at' => '2019-04-01 13:00:01',
                        'scheduled_end_at' => '2019-04-01 18:00:00',
                    ],
                ],
            ],
            [
                'test' => 'on time to work shift without gaps and launch time',
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'checked_in_at' => '2019-04-01 07:00:00', // on time
                    'checked_out_at' => '2019-04-01 18:00:00', // on time
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => (60 * 5) - 1,
                        'scheduled_start_at' => '2019-04-01 07:00:00',
                        'scheduled_end_at' => '2019-04-01 11:59:59',
                    ],
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => (60 * 5) - 1,
                        'scheduled_start_at' => '2019-04-01 13:00:01',
                        'scheduled_end_at' => '2019-04-01 18:00:00',
                    ],
                ],
            ],
            [
                'test' => 'too early to work shift without gaps and launch time',
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'check_in_novelty_type_code' => 'HEDI',
                    'checked_in_at' => '2019-04-02 06:00:00', // 1 hours early
                    'checked_out_at' => '2019-04-02 18:00:00', // on time
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => (60 * 5) - 1,
                        'scheduled_start_at' => '2019-04-02 07:00:00',
                        'scheduled_end_at' => '2019-04-02 11:59:59',
                    ],
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => (60 * 5) - 1,
                        'scheduled_start_at' => '2019-04-02 13:00:01',
                        'scheduled_end_at' => '2019-04-02 18:00:00',
                    ],
                    [
                        'novelty_type_code' => 'HEDI', // because of check_in_novelty_type_code
                        'total_time_in_minutes' => (60 * 1) - 1, // 1 hour early
                        'scheduled_start_at' => '2019-04-02 06:00:00',
                        'scheduled_end_at' => '2019-04-02 06:59:59',
                    ],
                ],
            ],
            [
                'test' => 'early check in and late check out with same novelty to work shift without gaps and launch time',
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
                        'total_time_in_minutes' => (60 * 5) - 1,
                        'scheduled_start_at' => '2019-04-03 07:00:00',
                        'scheduled_end_at' => '2019-04-03 11:59:59',
                    ],
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => (60 * 5) - 1,
                        'scheduled_start_at' => '2019-04-03 13:00:01',
                        'scheduled_end_at' => '2019-04-03 18:00:00',
                    ],
                    [
                        'novelty_type_code' => 'HEDI', // because of check_in_novelty_type_code
                        'total_time_in_minutes' => (60 * 1) - 1,
                        'scheduled_start_at' => '2019-04-03 06:00:00',
                        'scheduled_end_at' => '2019-04-03 06:59:59',
                    ],
                    [
                        'novelty_type_code' => 'HEDI', // because of check_out_novelty_type_code
                        'total_time_in_minutes' => (60 * 1) - 1,
                        'scheduled_start_at' => '2019-04-03 18:00:01',
                        'scheduled_end_at' => '2019-04-03 19:00:00',
                    ],
                ],
            ],
            [
                'test' => 'early check in and late check out with distinct novelty to work shift without gaps and launch time',
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'check_in_novelty_type_code' => 'HEDI',
                    'check_out_novelty_type_code' => 'HADI',
                    'checked_in_at' => '2019-04-03 06:00:00', // 1 hour early
                    'checked_out_at' => '2019-04-03 19:00:00', // 1 hour late
                    'sub_cost_center_id' => 1,
                    'check_out_sub_cost_center_id' => 2,
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => (60 * 5) - 1,
                        'scheduled_start_at' => '2019-04-03 07:00:00',
                        'scheduled_end_at' => '2019-04-03 11:59:59',
                    ],
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => (60 * 5) - 1,
                        'scheduled_start_at' => '2019-04-03 13:00:01',
                        'scheduled_end_at' => '2019-04-03 18:00:00',
                    ],
                    [
                        'novelty_type_code' => 'HEDI', // because of check_in_novelty_type_code
                        'total_time_in_minutes' => (60 * 1) - 1,
                        'scheduled_start_at' => '2019-04-03 06:00:00',
                        'scheduled_end_at' => '2019-04-03 06:59:59',
                    ],
                    [
                        'novelty_type_code' => 'HADI', // because of check_out_novelty_type_code
                        'total_time_in_minutes' => (60 * 1) - 1,
                        'scheduled_start_at' => '2019-04-03 18:00:01',
                        'scheduled_end_at' => '2019-04-03 19:00:00',
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
                        'scheduled_start_at' => '2019-04-01 13:30:00',
                        'scheduled_end_at' => '2019-04-01 17:00:00',
                    ],
                    [
                        'novelty_type_code' => 'HEDI',
                        'total_time_in_minutes' => (60 * 2) - 1, // 2 hours from 5pm to 7pm
                        'scheduled_start_at' => '2019-04-01 17:00:01',
                        'scheduled_end_at' => '2019-04-01 19:00:00',
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
                        'scheduled_start_at' => '2019-04-01 08:00:00',
                        'scheduled_end_at' => '2019-04-01 18:00:00',
                    ],
                    [
                        'novelty_type_code' => 'PP',
                        'total_time_in_minutes' => (60 - 1) * -1, // 1 hour from 7am to 8am
                        'scheduled_start_at' => '2019-04-01 07:00:00',
                        'scheduled_end_at' => '2019-04-01 07:59:59',
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
                        'scheduled_start_at' => '2019-04-01 08:00:00',
                        'scheduled_end_at' => '2019-04-01 12:30:00',
                    ],
                    [
                        'novelty_type_code' => 'PP',
                        'total_time_in_minutes' => (60 - 1) * -1, // 1 hour from 7am to 8am
                        'scheduled_start_at' => '2019-04-01 07:00:00',
                        'scheduled_end_at' => '2019-04-01 07:59:59',
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
                        'scheduled_start_at' => '2019-04-01 07:00:00',
                        'scheduled_end_at' => '2019-04-01 11:30:00',
                    ],
                    [
                        'novelty_type_code' => 'PP',
                        'total_time_in_minutes' => (60 - 1) * -1, // 1 hour from 11:30am to 12:30pm
                        'scheduled_start_at' => '2019-04-01 11:30:01',
                        'scheduled_end_at' => '2019-04-01 12:30:00',
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
                        'scheduled_start_at' => '2019-04-01 07:00:00',
                        'scheduled_end_at' => '2019-04-01 14:00:00',
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
                        'total_time_in_minutes' => (60 * 8) - 1,
                        'scheduled_start_at' => '2019-04-01 22:00:00',
                        'scheduled_end_at' => '2019-04-02 05:59:59',
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
                        'total_time_in_minutes' => (60 * 8) - 1,
                        'scheduled_start_at' => '2019-06-30 22:00:00',
                        'scheduled_end_at' => '2019-07-01 05:59:59',
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
                        'total_time_in_minutes' => (60 * 2) - 1,
                        'scheduled_start_at' => '2019-03-30 22:00:00',
                        'scheduled_end_at' => '2019-03-30 23:59:59',
                    ],
                    [
                        'novelty_type_code' => 'HNF',
                        'total_time_in_minutes' => (60 * 6) - 1,
                        'scheduled_start_at' => '2019-03-31 00:00:00',
                        'scheduled_end_at' => '2019-03-31 05:59:59',
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
                        'total_time_in_minutes' => (60 * 6) - 1,
                        'scheduled_start_at' => '2019-07-02 00:00:00',
                        'scheduled_end_at' => '2019-07-02 05:59:59',
                    ],
                    [
                        'novelty_type_code' => 'HNF',
                        'total_time_in_minutes' => (60 * 2) - 1,
                        'scheduled_start_at' => '2019-07-01 22:00:00',
                        'scheduled_end_at' => '2019-07-01 23:59:59',
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
                        'total_time_in_minutes' => (60 * 1) - 1,
                        'scheduled_start_at' => '2019-04-01 21:00:01',
                        'scheduled_end_at' => '2019-04-01 22:00:00',
                    ],
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => (60 * 7),
                        'scheduled_start_at' => '2019-04-01 14:00:00',
                        'scheduled_end_at' => '2019-04-01 21:00:00',
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
                        'total_time_in_minutes' => (60 * 1) - 1,
                        'scheduled_start_at' => '2019-07-01 21:00:01',
                        'scheduled_end_at' => '2019-07-01 22:00:00',
                    ],
                    [
                        'novelty_type_code' => 'HDF',
                        'total_time_in_minutes' => (60 * 7),
                        'scheduled_start_at' => '2019-07-01 14:00:00',
                        'scheduled_end_at' => '2019-07-01 21:00:00',
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
                        'total_time_in_minutes' => (60 * 8),
                        'scheduled_start_at' => '2019-04-01 06:00:00',
                        'scheduled_end_at' => '2019-04-01 14:00:00',
                    ],
                    [
                        'novelty_type_code' => 'HADI',
                        'total_time_in_minutes' => (60 * 1) - 1,
                        'scheduled_start_at' => '2019-04-01 05:00:00',
                        'scheduled_end_at' => '2019-04-01 05:59:59',
                    ],
                ],
            ],
            // ############################################################### #
            //     Time lock logs with too late check in or early check out    #
            // ############################################################### #
            [
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'check_in_novelty_type_code' => null, // empty novelty type
                    'checked_in_at' => '2019-04-01 08:00:00', // 1 hour late
                    'checked_out_at' => '2019-04-01 18:00:00', // on time
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        // 10 hours (from 8am to 6pm), minimum minutes to subtract launch time not reached
                        'total_time_in_minutes' => 60 * 10,
                        'scheduled_start_at' => '2019-04-01 08:00:00',
                        'scheduled_end_at' => '2019-04-01 18:00:00',
                    ],
                    [
                        'novelty_type_code' => 'PP', // default novelty type when check_in_novelty_type_id is null
                        'total_time_in_minutes' => (60 - 1) * -1, // 1 hour from 7am to 8am
                        'scheduled_start_at' => '2019-04-01 07:00:00',
                        'scheduled_end_at' => '2019-04-01 07:59:59',
                    ],
                ],
            ],
            // ############################################################### #
            //               Time lock logs with scheduled novelties           #
            // ############################################################### #
            [
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'checked_in_at' => '2019-04-01 09:00:00', // too late, because of scheduled novelty
                    'checked_out_at' => '2019-04-01 18:00:00', // on time
                    'sub_cost_center_id' => 1,
                    'check_in_novelty_type_code' => 'PP', // novelty for too late check in
                ],
                'scheduledNovelties' => [
                    [
                        'novelty_type_code' => 'CM',
                        'scheduled_start_at' => '2019-04-01 07:00:00',
                        'scheduled_end_at' => '2019-04-01 08:00:00', // this would be the expected time to check in
                        'total_time_in_minutes' => 60 * 1, // 1 hour from 7am to 8am
                    ],
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'CM', // this novelty should be now attached to time clock log record
                        'total_time_in_minutes' => 60 * 1, // 1 hour from 07:00 to 08:00
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                    ],
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => 60 * 9,
                        'scheduled_start_at' => '2019-04-01 09:00:00',
                        'scheduled_end_at' => '2019-04-01 18:00:00',
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                    ],
                    [
                        'novelty_type_code' => 'PP', // novelty for too late check in
                        'total_time_in_minutes' => (60 - 1) * -1,
                        'sub_cost_center_id' => 1,
                        'scheduled_start_at' => '2019-04-01 08:00:01',
                        'scheduled_end_at' => '2019-04-01 08:59:59',
                    ],
                ],
            ],
            [
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'sub_cost_center_id' => 1,
                    'check_out_novelty_type_code' => null, // empty novelty type
                    'checked_in_at' => '2019-04-01 07:00:00', // on time
                    'checked_out_at' => '2019-04-01 16:00:00', // on time, because of scheduled novelty
                ],
                'scheduledNovelties' => [
                    [
                        'novelty_type_code' => 'CM', // scheduled novelty for check out
                        'scheduled_start_at' => '2019-04-01 16:00:00',
                        'scheduled_end_at' => '2019-04-01 18:00:00',
                        'total_time_in_minutes' => 60 * 2, // 2 hours from 4pm to 6pm
                    ],
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        // 9 hours (from 7am to 4pm), minimum minutes to subtract launch time not reached
                        'total_time_in_minutes' => (60 * 9) - 1,
                        'scheduled_start_at' => '2019-04-01 07:00:00',
                        'scheduled_end_at' => '2019-04-01 15:59:59',
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                    ],
                    [
                        'novelty_type_code' => 'CM', // this novelty should be now attached to time clock log record
                        'total_time_in_minutes' => 60 * 2,
                        'scheduled_start_at' => '2019-04-01 16:00:00',
                        'scheduled_end_at' => '2019-04-01 18:00:00',
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                    ],
                ],
            ],
            // HERE!!!
            [
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'check_out_novelty_type_code' => null, // empty novelty type
                    'checked_in_at' => '2019-04-01 09:00:00', // too late, because of scheduled novelty
                    'checked_out_at' => '2019-04-01 16:00:00', // too early, because of scheduled novelty
                    'sub_cost_center_id' => 1,
                ],
                'scheduledNovelties' => [
                    [
                        'novelty_type_code' => 'CM', // scheduled novelty for check in
                        'scheduled_start_at' => '2019-04-01 07:00:00',
                        'scheduled_end_at' => '2019-04-01 08:00:00',
                        'total_time_in_minutes' => 60 * 1, // 1 hour from 7am to 8am
                    ],
                    [
                        'novelty_type_code' => 'CM', // scheduled novelty for check out
                        'scheduled_start_at' => '2019-04-01 17:00:00',
                        'scheduled_end_at' => '2019-04-01 18:00:00',
                        'total_time_in_minutes' => 60 * 1, // 1 hour from 5pm to 6pm
                    ],
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'CM', // this novelty should be now attached to time clock log record
                        'total_time_in_minutes' => 60 * 1, // 1 hour from 7am to 8am
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                        'scheduled_start_at' => '2019-04-01 07:00:00',
                        'scheduled_end_at' => '2019-04-01 08:00:00',
                    ],
                    [
                        'novelty_type_code' => 'CM', // this novelty should be now attached to time clock log record
                        'total_time_in_minutes' => 60 * 1, // 1 hour from 5pm to 6pm
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                        'scheduled_start_at' => '2019-04-01 17:00:00',
                        'scheduled_end_at' => '2019-04-01 18:00:00',
                    ],
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => 60 * 7,
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                        'scheduled_start_at' => '2019-04-01 09:00:00',
                        'scheduled_end_at' => '2019-04-01 16:00:00',
                    ],
                    [
                        'novelty_type_code' => 'PP', // novelty for early check out
                        'total_time_in_minutes' => (60 - 1) * -1,
                        'scheduled_start_at' => '2019-04-01 08:00:01',
                        'scheduled_end_at' => '2019-04-01 08:59:59',
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                    ],
                    [
                        'novelty_type_code' => 'PP', // novelty for late check out
                        'total_time_in_minutes' => (60 - 1) * -1,
                        'scheduled_start_at' => '2019-04-01 08:00:01',
                        'scheduled_end_at' => '2019-04-01 08:59:59',
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                    ],
                ],
            ],
            [
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'check_in_novelty_type_code' => 'PP', // because check in is 1 hour late
                    'checked_in_at' => '2019-04-01 08:00:00', // too late, because work shift
                    'checked_out_at' => '2019-04-01 10:00:00', // on time, because scheduled novelty
                    'sub_cost_center_id' => 1,
                ],
                'scheduledNovelties' => [
                    [
                        'novelty_type_code' => 'CM', // scheduled novelty for check in
                        'scheduled_start_at' => '2019-04-01 10:00:00',
                        'scheduled_end_at' => '2019-04-01 11:00:00',
                        'total_time_in_minutes' => 60 * 1, // 1 hour from 7am to 8am
                    ],
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => (60 * 2) - 1,
                        'scheduled_start_at' => '2019-04-01 08:00:00',
                        'scheduled_end_at' => '2019-04-01 09:59:59',
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                    ],
                    [
                        'novelty_type_code' => 'CM',
                        'total_time_in_minutes' => 60 * 1, // 1 hour from 10am to 11am
                        'sub_cost_center_id' => 1,
                        'scheduled_start_at' => '2019-04-01 10:00:00',
                        'scheduled_end_at' => '2019-04-01 11:00:00',
                    ],
                    [
                        'novelty_type_code' => 'PP', // novelty for late check in and early check out
                        'total_time_in_minutes' => (60 - 1) * -1,
                        'scheduled_start_at' => '2019-04-01 07:00:00',
                        'scheduled_end_at' => '2019-04-01 07:59:59',
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                    ],
                ],
            ],
            [
                'timeClockLog' => [ // time clock log on sunday
                    'work_shift_name' => '14-22 Sundays',
                    'checked_in_at' => '2019-07-21 16:00:00', // sunday, two hours late
                    'checked_out_at' => '2019-07-21 17:00:00', // sunday, five hours early
                    'check_in_novelty_type_code' => 'PP', // for the start time not worked
                    'check_out_novelty_type_code' => 'PP', // for the final time not worked
                    'sub_cost_center_id' => 1,
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'PP',
                        'total_time_in_minutes' => (60 * -2) + 1,
                        'scheduled_start_at' => '2019-07-21 14:00:00',
                        'scheduled_end_at' => '2019-07-21 15:59:59',
                        'sub_cost_center_id' => 1,
                    ],
                    [
                        'novelty_type_code' => 'PP',
                        'total_time_in_minutes' => (60 * -5) + 1,
                        'scheduled_start_at' => '2019-07-21 17:00:01',
                        'scheduled_end_at' => '2019-07-21 22:00:00',
                        'sub_cost_center_id' => 1,
                    ],
                    [
                        'novelty_type_code' => 'HDF',
                        'total_time_in_minutes' => (60 * 1),
                        'scheduled_start_at' => '2019-07-21 16:00:00',
                        'scheduled_end_at' => '2019-07-21 17:00:00',
                        'sub_cost_center_id' => 1,
                    ],
                ],
            ],
            [
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'check_out_novelty_type_code' => null, // empty novelty type
                    'checked_in_at' => '2019-04-01 07:00:00', // on time
                    'checked_out_at' => '2019-04-01 17:00:00', // one hour early
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'scheduled_start_at' => '2019-04-01 07:00:00',
                        'scheduled_end_at' => '2019-04-01 17:00:00',
                        'total_time_in_minutes' => 60 * 10,
                    ],
                    [
                        'novelty_type_code' => 'PP', // default novelty type when check_in_novelty_type_id is null
                        'scheduled_start_at' => '2019-04-01 17:00:01',
                        'scheduled_end_at' => '2019-04-01 18:00:00',
                        'total_time_in_minutes' => (60 - 1) * -1,
                    ],
                ],
            ],
            [
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'sub_cost_center_id' => 1,
                    'check_out_novelty_type_code' => null, // empty novelty type
                    'checked_in_at' => '2019-04-01 08:00:00', // on time, because of scheduled novelty
                    'checked_out_at' => '2019-04-01 18:00:00', // on time
                ],
                'scheduledNovelties' => [
                    [
                        'novelty_type_code' => 'CM', // scheduled novelty for check in
                        'scheduled_start_at' => '2019-04-01 07:00:00',
                        'scheduled_end_at' => '2019-04-01 08:00:00',
                        'total_time_in_minutes' => 60 * 1, // 1 hour from 7am to 8am
                    ],
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => (60 * 10) - 1,
                        'scheduled_start_at' => '2019-04-01 08:00:01',
                        'scheduled_end_at' => '2019-04-01 18:00:00',
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                    ],
                    [
                        'novelty_type_code' => 'CM', // this novelty should be now attached to time clock log record
                        'total_time_in_minutes' => 60 * 1,
                        'scheduled_start_at' => '2019-04-01 07:00:00',
                        'scheduled_end_at' => '2019-04-01 08:00:00',
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                    ],
                ],
            ],
            [
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'check_out_novelty_type_code' => null, // empty novelty type
                    'checked_in_at' => '2019-04-01 09:00:00', // on time, because of scheduled novelty
                    'checked_out_at' => '2019-04-01 16:00:00', // on time, because of scheduled novelty
                    'sub_cost_center_id' => 1,
                ],
                'scheduledNovelties' => [
                    [
                        'novelty_type_code' => 'CM', // scheduled novelty for check in
                        'scheduled_start_at' => '2019-04-01 07:00:00',
                        'scheduled_end_at' => '2019-04-01 09:00:00',
                        'total_time_in_minutes' => 60 * 2, // 2 hours from 7am to 9am
                    ],
                    [
                        'novelty_type_code' => 'CM', // scheduled novelty for check out
                        'scheduled_start_at' => '2019-04-01 16:00:00',
                        'scheduled_end_at' => '2019-04-01 18:00:00',
                        'total_time_in_minutes' => 60 * 2, // 2 hours from 4pm to 6pm
                    ],
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => (60 * 7) - 1,
                        'scheduled_start_at' => '2019-04-01 09:00:01',
                        'scheduled_end_at' => '2019-04-01 15:59:59',
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                    ],
                    [
                        'novelty_type_code' => 'CM', // this novelty should be now attached to time clock log record
                        'total_time_in_minutes' => 60 * 2,
                        'scheduled_start_at' => '2019-04-01 07:00:00',
                        'scheduled_end_at' => '2019-04-01 09:00:00',
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                    ],
                    [
                        'novelty_type_code' => 'CM', // this novelty should be now attached to time clock log record
                        'total_time_in_minutes' => 60 * 2,
                        'scheduled_start_at' => '2019-04-01 16:00:00',
                        'scheduled_end_at' => '2019-04-01 18:00:00',
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                    ],
                ],
            ],
            [ // time zone on this scenario is UTC but work shift is America/Bogota!!
                'timeClockLog' => [
                    'work_shift_name' => '6-14 America/Bogota', // work shift with non UTC time zone
                    'sub_cost_center_id' => 1,
                    'check_out_novelty_type_code' => null, // empty novelty type
                    'checked_in_at' => '2019-04-01 11:00:00', // on time, because work shift
                    'checked_out_at' => '2019-04-01 15:00:00', // on time, because scheduled novelty
                ],
                'scheduledNovelties' => [
                    [
                        'novelty_type_code' => 'CM', // scheduled novelty for check out
                        'scheduled_start_at' => '2019-04-01 15:00:00',
                        'scheduled_end_at' => '2019-04-01 16:00:00',
                        'total_time_in_minutes' => 60 * 1, // 1 hour from 10am to 11am
                    ],
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'total_time_in_minutes' => (60 * 4) - 1,
                        // 'scheduled_start_at' => '2019-04-01 11:00:00',
                        // 'scheduled_end_at' => '2019-04-01 14:59:59',
                        'sub_cost_center_id' => 1, // from time clock log sub cost center id
                    ],
                    [
                        'novelty_type_code' => 'CM', // this novelty should be now attached to time clock log record
                        'total_time_in_minutes' => 60 * 1,
                        // 'scheduled_start_at' => '2019-04-01 15:00:00',
                        // 'scheduled_end_at' => '2019-04-01 16:00:00',
                        'sub_cost_center_id' => 1, // from time clock log sub cost center id
                    ],
                ],
            ],
            [
                'timeClockLog' => [ // time clock log on workday
                    'work_shift_name' => '14-22',
                    'checked_in_at' => '2019-04-01 12:00:00', // workday, two hours early
                    'checked_out_at' => '2019-04-01 13:30:00', // workday, two hours early, before shift start
                    'check_in_novelty_type_code' => 'HADI',
                    'check_in_sub_cost_center_id' => 2,
                    'sub_cost_center_id' => 1,
                    'check_out_novelty_type_code' => 'PP', // for the time not worked, the entire work shift
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HADI',
                        'total_time_in_minutes' => 90, // 1.5 hours, from 12:00:00 to 13:30
                        'sub_cost_center_id' => 2,
                    ],
                    [
                        'novelty_type_code' => 'PP',
                        'total_time_in_minutes' => (60 * -8), // -8 hours, from 14:00 to 22:00
                        'sub_cost_center_id' => 1,
                    ],
                ],
            ],
            /**//**/
        ];
    }

    /**
     * @test
     * @dataProvider successCases
     * @param IntegrationTester $I
     */
    public function testToRunAction(IntegrationTester $I, Example $data)
    {
        $scheduledNovelties = $data['scheduledNovelties'] ?? [];
        $timeClockData = $this->mapTimeClockData($data['timeClockLog']);
        $timeClockLog = factory(TimeClockLog::class)->create($timeClockData);

        // create scheduled novelties
        foreach ($scheduledNovelties as $scheduledNovelty) {
            $noveltyType = $this->noveltyTypes->firstWhere('code', $scheduledNovelty['novelty_type_code']);

            factory(Novelty::class)->create([
                'employee_id' => $timeClockLog->employee->id,
                'novelty_type_id' => $noveltyType->id,
                'scheduled_start_at' => $scheduledNovelty['scheduled_start_at'],
                'scheduled_end_at' => $scheduledNovelty['scheduled_end_at'],
                'total_time_in_minutes' => $scheduledNovelty['total_time_in_minutes'],
                'time_clock_log_id' => null,
            ]);
        }

        $action = app(RegisterTimeClockNoveltiesAction::class);
        $I->assertTrue($action->run($timeClockLog->id));

        // only one novelty should be created
        $createdRecordsCount = $I->grabNumRecords('novelties', [
            'time_clock_log_id' => $timeClockLog->id,
            'employee_id' => optional($timeClockLog->employee)->id,
        ]);

        $I->assertEquals(count($data['createdNovelties']), $createdRecordsCount, 'created novelties count');

        foreach ($data['createdNovelties'] as $novelty) {
            $noveltyType = $this->noveltyTypes->firstWhere('code', $novelty['novelty_type_code']);
            $times = array_filter([
                'scheduled_start_at' => $novelty['scheduled_start_at'] ?? null,
                'scheduled_end_at' => $novelty['scheduled_end_at'] ?? null,
            ]);

            $I->seeRecord('novelties', $times + [
                'time_clock_log_id' => $timeClockLog->id,
                'employee_id' => $timeClockLog->employee->id,
                'novelty_type_id' => $noveltyType->id,
                'total_time_in_minutes' => $novelty['total_time_in_minutes'],
                // 'sub_cost_center_id' => $novelty['sub_cost_center_id'] ?? null,
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

    /**
     * @test
     * @param IntegrationTester $I
     */
    public function whenHasLateCheckOutWithNoveltyOnMorningAndCheckInAgainOnAfternoon(IntegrationTester $I)
    {
        Carbon::setTestNow(Carbon::parse('2019-04-01')); // monday workday

        // morning log with check out addition novelty due to late check out
        $morningLog = factory(TimeClockLog::class)->create([
            'work_shift_id' => $this->workShifts->where('name', '7-12 13:30-17:00')->first()->id,
            'checked_in_at' => now()->setTime(06, 58),
            'checked_out_at' => now()->setTime(12, 30),
            'check_out_novelty_type_id' => $this->noveltyTypes->where('code', 'HADI')->first()->id,
            'check_out_sub_cost_center_id' => $this->subCostCenters->first()->id,
        ]);

        factory(Novelty::class)->create([
            'employee_id' => $morningLog->employee_id,
            'time_clock_log_id' => $morningLog->id,
            'novelty_type_id' => $this->noveltyTypes->where('code', 'HN')->first()->id,
            'scheduled_start_at' => now()->setTime(07, 00),
            'scheduled_end_at' => now()->setTime(12, 00),
            'total_time_in_minutes' => 60 * 5,
        ]);

        factory(Novelty::class)->create([
            'employee_id' => $morningLog->employee_id,
            'time_clock_log_id' => $morningLog->id,
            'novelty_type_id' => $this->noveltyTypes->where('code', 'HADI')->first()->id,
            'scheduled_start_at' => now()->setTime(12, 00),
            'scheduled_end_at' => now()->setTime(12, 30),
            'total_time_in_minutes' => 30,
        ]);

        // afternoon log with late check in
        $afternoonLog = factory(TimeClockLog::class)->create([
            'work_shift_id' => $this->workShifts->where('name', '7-12 13:30-17:00')->first()->id,
            'employee_id' => $morningLog->employee_id,
            'checked_in_at' => now()->setTime(14, 30), // late check in, 1 hour late
            'checked_out_at' => now()->setTime(17, 30), // late check out, 0.5 hours late
            'check_in_novelty_type_id' => $this->noveltyTypes->where('code', 'PP')->first()->id,
            'check_out_novelty_type_id' => $this->noveltyTypes->where('code', 'HADI')->first()->id,
            'check_out_sub_cost_center_id' => $this->subCostCenters->first()->id,
        ]);

        $action = app(RegisterTimeClockNoveltiesAction::class);
        $result = $action->run($afternoonLog->id);

        $I->assertTrue($result);

        $I->seeRecord('novelties', [ // ordinary time
            'time_clock_log_id' => $afternoonLog->id,
            'novelty_type_id' => $this->noveltyTypes->where('code', 'HN')->first()->id,
            'scheduled_start_at' => '2019-04-01 14:30:00',
            'scheduled_end_at' => '2019-04-01 17:00:00',
            'total_time_in_minutes' => (60 * 3) - 30,
        ]);

        $I->seeRecord('novelties', [ // additional time
            'time_clock_log_id' => $afternoonLog->id,
            'novelty_type_id' => $this->noveltyTypes->where('code', 'HADI')->first()->id,
            'scheduled_start_at' => '2019-04-01 17:00:01',
            'scheduled_end_at' => '2019-04-01 17:30:00',
            'total_time_in_minutes' => 29,
        ]);

        $I->seeRecord('novelties', [ // missing time
            'time_clock_log_id' => $afternoonLog->id,
            'novelty_type_id' => $this->noveltyTypes->where('code', 'PP')->first()->id,
            'scheduled_start_at' => '2019-04-01 13:30:00',
            'scheduled_end_at' => '2019-04-01 14:29:59',
            'total_time_in_minutes' => -59,
        ]);

        $I->seeNumRecords(3, 'novelties', ['time_clock_log_id' => $afternoonLog->id]);
    }

    /**
     * @test
     * @param IntegrationTester $I
     */
    public function shouldBeAwareFromPreviousClockedTimeOnSameWorkShift(IntegrationTester $I)
    {
        Carbon::setTestNow(Carbon::parse('2019-04-01'));

        // morning log with attached addition novelty due to late check out
        $morningLog = factory(TimeClockLog::class)->create([
            'work_shift_id' => $this->workShifts->where('name', '7-18')->first()->id,
            'checked_in_at' => now()->setTime(06, 58),
            'checked_out_at' => now()->setTime(12, 02),
            'check_out_novelty_type_id' => null,
            'check_out_sub_cost_center_id' => $this->subCostCenters->first()->id,
        ]);

        factory(Novelty::class)->create([
            'employee_id' => $morningLog->employee_id,
            'time_clock_log_id' => $morningLog->id,
            'novelty_type_id' => $this->noveltyTypes->where('code', 'HN')->first()->id,
            'scheduled_start_at' => now()->setTime(07, 00),
            'scheduled_end_at' => now()->setTime(12, 00),
            'total_time_in_minutes' => 60 * 5,
        ]);

        // scheduled novelty for morning log
        factory(Novelty::class)->create([
            'employee_id' => $morningLog->employee_id,
            'time_clock_log_id' => $morningLog->id,
            'novelty_type_id' => $this->noveltyTypes->where('code', 'PP')->first()->id,
            'scheduled_start_at' => now()->setTime(12, 00),
            'scheduled_end_at' => now()->setTime(14, 00), // next check in should be at 2pm
            'total_time_in_minutes' => 60 * 2,
        ]);

        // afternoon log, after scheduled novelty
        $afternoonLog = factory(TimeClockLog::class)->create([
            'work_shift_id' => $this->workShifts->where('name', '7-18')->first()->id,
            'employee_id' => $morningLog->employee_id,
            'checked_in_at' => now()->setTime(14, 00), // on time
            'checked_out_at' => now()->setTime(18, 30), // late check out, 0.5 hours late
            'check_in_novelty_type_id' => null,
            'check_out_novelty_type_id' => $this->noveltyTypes->where('code', 'HADI')->first()->id,
            'check_out_sub_cost_center_id' => $this->subCostCenters->first()->id,
        ]);

        $action = app(RegisterTimeClockNoveltiesAction::class);
        $result = $action->run($afternoonLog->id);

        $I->assertTrue($result);

        $I->seeRecord('novelties', [ // ordinary time
            'time_clock_log_id' => $afternoonLog->id,
            'novelty_type_id' => $this->noveltyTypes->where('code', 'HN')->first()->id,
            'total_time_in_minutes' => 60 * 4,
            'scheduled_start_at' => '2019-04-01 14:00:00',
            'scheduled_end_at' => '2019-04-01 18:00:00',
        ]);

        $I->seeRecord('novelties', [ // additional time
            'time_clock_log_id' => $afternoonLog->id,
            'novelty_type_id' => $this->noveltyTypes->where('code', 'HADI')->first()->id,
            'scheduled_start_at' => '2019-04-01 18:00:01',
            'scheduled_end_at' => '2019-04-01 18:30:00',
            'total_time_in_minutes' => 29,
        ]);

        $I->seeNumRecords(2, 'novelties', ['time_clock_log_id' => $afternoonLog->id]);
    }

    /**
     * @test
     * @param IntegrationTester $I
     */
    public function shouldBeAwareOfDistinctWorkShiftTimeZones(IntegrationTester $I)
    {
        // default values on America/Bogota timezone
        $I->callArtisan('db:seed', ['--class' => DefaultWorkShiftsSeeder::class]);
        $I->callArtisan('db:seed', ['--class' => DefaultNoveltyTypesSeed::class]);

        $noveltyTypes = NoveltyType::all();

        // data is stored in UTC
        $log = factory(TimeClockLog::class)->create([
            'sub_cost_center_id' => factory(SubCostCenter::class)->create()->id,
            'work_shift_id' => WorkShift::where('name', '07-18')->first()->id,
            'checked_in_at' => '2021-04-12 11:00:00',
            'checked_out_at' => '2021-04-12 19:00:00',
        ]);

        $action = app(RegisterTimeClockNoveltiesAction::class);
        $action->run($log->id);

        $I->seeRecord('novelties', [
            'time_clock_log_id' => $log->id,
            'novelty_type_id' => $noveltyTypes->firstWhere('code', 'HADI')->id,
            'scheduled_start_at' => '2021-04-12 11:00:00',
            'scheduled_end_at' => '2021-04-12 11:59:59',
        ]);

        $I->seeRecord('novelties', [
            'time_clock_log_id' => $log->id,
            'novelty_type_id' => $noveltyTypes->firstWhere('code', 'HN')->id,
            'scheduled_start_at' => '2021-04-12 12:00:00',
            'scheduled_end_at' => '2021-04-12 17:00:00',
        ]);

        $I->seeRecord('novelties', [
            'time_clock_log_id' => $log->id,
            'novelty_type_id' => $noveltyTypes->firstWhere('code', 'HADI')->id,
            'scheduled_start_at' => '2021-04-12 17:00:01',
            'scheduled_end_at' => '2021-04-12 17:59:59',
        ]);

        $I->seeRecord('novelties', [
            'time_clock_log_id' => $log->id,
            'novelty_type_id' => $noveltyTypes->firstWhere('code', 'HN')->id,
            'scheduled_start_at' => '2021-04-12 18:00:00',
            'scheduled_end_at' => '2021-04-12 19:00:00',
        ]);
    }
}
