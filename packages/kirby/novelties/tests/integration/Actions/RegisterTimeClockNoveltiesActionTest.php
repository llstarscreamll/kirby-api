<?php

namespace Kirby\Novelties\Tests\Actions;

use Carbon\Carbon;
use DefaultNoveltyTypesSeed;
use DefaultWorkShiftsSeeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Kirby\Company\Models\Holiday;
use Kirby\Company\Models\SubCostCenter;
use Kirby\Employees\Models\Employee;
use Kirby\Novelties\Actions\RegisterTimeClockNoveltiesAction;
use Kirby\Novelties\Models\Novelty;
use Kirby\Novelties\Models\NoveltyType;
use Kirby\Novelties\Novelties;
use Kirby\TimeClock\Models\TimeClockLog;
use Kirby\WorkShifts\Models\WorkShift;
use NoveltiesPackageSeed;

/**
 * Class RegisterTimeClockNoveltiesActionTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class RegisterTimeClockNoveltiesActionTest extends \Tests\TestCase
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

    public function setUp(): void
    {
        parent::setUp();
        $this->seed(NoveltiesPackageSeed::class);
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

    public function successCases()
    {
        return [
            [
                'wantTo' => 'test-1',
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'checked_in_at' => '2019-04-01 07:00:01', // on time
                    'checked_out_at' => null, // without checkout
                    'check_out_novelty_type_code' => null,
                    'sub_cost_center_id' => null,
                ],
                'expectedOutPut' => false,
                'scheduledNovelties' => [],
                'createdNovelties' => [], // noting should be created
            ],
            [
                'wantTo' => 'test-2',
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'checked_in_at' => '2019-04-01 07:00:01', // on time
                    'checked_out_at' => '2019-04-01 07:01:10', // one minutes after check in
                    'check_out_novelty_type_code' => 'PP',
                    'sub_cost_center_id' => 1,
                ],
                'expectedOutPut' => false,
                'scheduledNovelties' => [],
                'createdNovelties' => [], // noting should be created
            ],
            [
                'wantTo' => 'test-3',
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'checked_in_at' => '2019-04-01 07:00:00', // on time
                    'checked_out_at' => '2019-04-01 16:00:00', // too early, because of scheduled novelty
                    'check_out_novelty_type_code' => 'PP', // novelty for early check out
                    'sub_cost_center_id' => 1,
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [
                    [
                        'novelty_type_code' => 'CM', // scheduled novelty for check out
                        'start_at' => '2019-04-01 17:00:00',
                        'end_at' => '2019-04-01 18:00:00',
                    ],
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        // 9 hours (from 7am to 4pm), minimum minutes to subtract launch time not reached
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                        'start_at' => '2019-04-01 07:00:00',
                        'end_at' => '2019-04-01 16:00:00',
                    ],
                    [
                        'novelty_type_code' => 'CM', // this novelty should be now attached to time clock log record
                        'start_at' => '2019-04-01 17:00:00',
                        'end_at' => '2019-04-01 18:00:00',
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                    ],
                    [
                        'novelty_type_code' => 'PP', // novelty for too early check out
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                        'start_at' => '2019-04-01 16:00:01',
                        'end_at' => '2019-04-01 16:59:59',
                    ],
                ],
            ],
            [
                'wantTo' => 'test-4',
                'timeClockLog' => [
                    // without work shift
                    'work_shift_name' => null,
                    'check_in_novelty_type_code' => 'HADI', // additional time
                    'checked_in_at' => '2019-03-31 08:00:00',
                    'checked_out_at' => '2019-03-31 14:00:00',
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HADI',
                        'start_at' => '2019-03-31 08:00:00',
                        'end_at' => '2019-03-31 14:00:00',
                    ],
                ],
            ],
            [
                'wantTo' => 'test-5',
                'timeClockLog' => [ // check in/out without work shift on holiday
                    'work_shift_name' => null, // without work shift
                    'check_in_novelty_type_code' => null, // without checkin novelty type
                    'checked_in_at' => '2019-03-31 08:00:00',
                    'checked_out_at' => '2019-03-31 14:00:00',
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HADI',
                        'start_at' => '2019-03-31 08:00:00',
                        'end_at' => '2019-03-31 14:00:00',
                    ],
                ],
            ],
            [
                'wantTo' => 'test-6',
                'timeClockLog' => [ // check in/out without work shift on workday
                    'work_shift_name' => null, // without work shift
                    'check_in_novelty_type_code' => null, // without checkin novelty type
                    'checked_in_at' => '2019-01-10 08:00:00',
                    'checked_out_at' => '2019-01-10 14:00:00',
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HADI',
                        'start_at' => '2019-01-10 08:00:00',
                        'end_at' => '2019-01-10 14:00:00',
                    ],
                ],
            ],
            [
                'wantTo' => 'test-7',
                'timeClockLog' => [
                    'work_shift_name' => '7-12 13:30-17:00',
                    'check_in_novelty_type_code' => null,
                    'checked_in_at' => '2019-04-01 06:48:00', // on time fot work shift
                    'checked_out_at' => '2019-04-01 08:00:00', // on time for scheduled novelty
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [
                    [
                        'novelty_type_code' => 'CM', // scheduled novelty for check out
                        'start_at' => '2019-04-01 08:00:00',
                        'end_at' => '2019-04-01 12:00:00',
                    ],
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'start_at' => '2019-04-01 07:00:00',
                        'end_at' => '2019-04-01 07:59:59',
                    ],
                    [
                        'novelty_type_code' => 'CM',
                        'start_at' => '2019-04-01 08:00:00',
                        'end_at' => '2019-04-01 12:00:00',
                    ],
                ],
            ],
            [
                'wantTo' => 'test-8',
                'timeClockLog' => [
                    'work_shift_name' => '7-16',
                    'check_in_novelty_type_code' => 'HADI',
                    'checked_in_at' => '2019-04-01 06:00:00', // too early
                    'checked_out_at' => '2019-04-01 16:00:00', // on time, with 12m-13pm gap reached
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'start_at' => '2019-04-01 07:00:00',
                        'end_at' => '2019-04-01 12:00:00',
                    ],
                    [
                        'novelty_type_code' => 'HN',
                        'start_at' => '2019-04-01 13:00:00',
                        'end_at' => '2019-04-01 16:00:00',
                    ],
                    [
                        'novelty_type_code' => 'HADI',
                        'start_at' => '2019-04-01 06:00:00',
                        'end_at' => '2019-04-01 06:59:59',
                    ],
                    [
                        'novelty_type_code' => 'HADI',
                        'start_at' => '2019-04-01 12:00:01',
                        'end_at' => '2019-04-01 12:59:59',
                    ],
                ],
            ],
            [
                'wantTo' => 'too early check in with novelty, too late check out without novelty, work shift with gap and only one check out',
                'timeClockLog' => [
                    'work_shift_name' => '7-16',
                    'check_in_novelty_type_code' => 'HADI',
                    'check_out_novelty_type_code' => null,
                    'checked_in_at' => '2019-04-01 05:00:00', // too early
                    'checked_out_at' => '2019-04-01 18:00:00', // too late, without checkout at 12m
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'start_at' => '2019-04-01 07:00:00',
                        'end_at' => '2019-04-01 12:00:00',
                    ],
                    [
                        'novelty_type_code' => 'HN',
                        'start_at' => '2019-04-01 13:00:00',
                        'end_at' => '2019-04-01 16:00:00',
                    ],
                    [
                        'novelty_type_code' => 'HADI',
                        'start_at' => '2019-04-01 05:00:00',
                        'end_at' => '2019-04-01 06:59:59',
                    ],
                    [
                        'novelty_type_code' => 'HADI',
                        'start_at' => '2019-04-01 12:00:01',
                        'end_at' => '2019-04-01 12:59:59',
                    ],
                    [
                        'novelty_type_code' => 'HADI', // without check out novelty, then should be HADI time by default
                        'start_at' => '2019-04-01 16:00:01',
                        'end_at' => '2019-04-01 18:00:00',
                    ],
                ],
            ],
            [
                'wantTo' => 'on time to work shift with one gap but only one check out at the end of second time slot',
                'timeClockLog' => [
                    'work_shift_name' => '7-16',
                    'checked_in_at' => '2019-04-01 07:00:00', // on time
                    'checked_out_at' => '2019-04-01 16:00:00', // on time, without checkout at 12m
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'start_at' => '2019-04-01 07:00:00',
                        'end_at' => '2019-04-01 12:00:00',
                    ],
                    [
                        'novelty_type_code' => 'HN',
                        'start_at' => '2019-04-01 13:00:00',
                        'end_at' => '2019-04-01 16:00:00',
                    ],
                    [
                        'novelty_type_code' => 'HADI',
                        'start_at' => '2019-04-01 12:00:01',
                        'end_at' => '2019-04-01 12:59:59',
                    ],
                ],
            ],
            [
                'wantTo' => 'too late check, closest to the end of first part of work shift',
                'timeClockLog' => [
                    'work_shift_name' => '7-12 13:30-17:00',
                    'checked_in_at' => '2019-04-01 11:49:00', // too late to first shift time slot
                    'checked_out_at' => '2019-04-01 12:15:00', // on time to first shift time slot, with grace time
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'start_at' => '2019-04-01 11:49:00',
                        'end_at' => '2019-04-01 12:00:00', // rounded to work shift first slot end
                    ],
                    [
                        'novelty_type_code' => 'PP',
                        'start_at' => '2019-04-01 07:00:00',
                        'end_at' => '2019-04-01 11:48:59',
                    ],
                ],
            ],
            [
                'wantTo' => 'soft limits touched on shift without gaps and meal time',
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'checked_in_at' => '2019-04-01 06:55:00', // on time, with grace time
                    'checked_out_at' => '2019-04-01 17:50:00', // on time, with grace time
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'start_at' => '2019-04-01 07:00:00',
                        'end_at' => '2019-04-01 11:59:59',
                    ],
                    [
                        'novelty_type_code' => 'HN',
                        'start_at' => '2019-04-01 13:00:01',
                        'end_at' => '2019-04-01 18:00:00',
                    ],
                ],
            ],
            [
                'wantTo' => 'on time to work shift without gaps and launch time',
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'checked_in_at' => '2019-04-01 07:00:00', // on time
                    'checked_out_at' => '2019-04-01 18:00:00', // on time
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'start_at' => '2019-04-01 07:00:00',
                        'end_at' => '2019-04-01 11:59:59',
                    ],
                    [
                        'novelty_type_code' => 'HN',
                        'start_at' => '2019-04-01 13:00:01',
                        'end_at' => '2019-04-01 18:00:00',
                    ],
                ],
            ],
            [
                'wantTo' => 'too early to work shift without gaps and launch time',
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'check_in_novelty_type_code' => 'HEDI',
                    'checked_in_at' => '2019-04-02 06:00:00', // 1 hours early
                    'checked_out_at' => '2019-04-02 18:00:00', // on time
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'start_at' => '2019-04-02 07:00:00',
                        'end_at' => '2019-04-02 11:59:59',
                    ],
                    [
                        'novelty_type_code' => 'HN',
                        'start_at' => '2019-04-02 13:00:01',
                        'end_at' => '2019-04-02 18:00:00',
                    ],
                    [
                        'novelty_type_code' => 'HEDI', // because of check_in_novelty_type_code
                        'start_at' => '2019-04-02 06:00:00',
                        'end_at' => '2019-04-02 06:59:59',
                    ],
                ],
            ],
            [
                'wantTo' => 'early check in and late check out with same novelty to work shift without gaps and launch time',
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'check_in_novelty_type_code' => 'HEDI', // extra daytime
                    'check_out_novelty_type_code' => 'HEDI', // extra daytime
                    'checked_in_at' => '2019-04-03 06:00:00', // 1 hour early
                    'checked_out_at' => '2019-04-03 19:00:00', // 1 hour late
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'start_at' => '2019-04-03 07:00:00',
                        'end_at' => '2019-04-03 11:59:59',
                    ],
                    [
                        'novelty_type_code' => 'HN',
                        'start_at' => '2019-04-03 13:00:01',
                        'end_at' => '2019-04-03 18:00:00',
                    ],
                    [
                        'novelty_type_code' => 'HEDI', // because of check_in_novelty_type_code
                        'start_at' => '2019-04-03 06:00:00',
                        'end_at' => '2019-04-03 06:59:59',
                    ],
                    [
                        'novelty_type_code' => 'HEDI', // because of check_out_novelty_type_code
                        'start_at' => '2019-04-03 18:00:01',
                        'end_at' => '2019-04-03 19:00:00',
                    ],
                ],
            ],
            [
                'wantTo' => 'early check in and late check out with distinct novelty to work shift without gaps and launch time',
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'check_in_novelty_type_code' => 'HEDI',
                    'check_out_novelty_type_code' => 'HADI',
                    'checked_in_at' => '2019-04-03 06:00:00', // 1 hour early
                    'checked_out_at' => '2019-04-03 19:00:00', // 1 hour late
                    'sub_cost_center_id' => 1,
                    'check_out_sub_cost_center_id' => 2,
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'sub_cost_center_id' => 1,
                        'start_at' => '2019-04-03 07:00:00',
                        'end_at' => '2019-04-03 11:59:59',
                    ],
                    [
                        'novelty_type_code' => 'HN',
                        'sub_cost_center_id' => 1,
                        'start_at' => '2019-04-03 13:00:01',
                        'end_at' => '2019-04-03 18:00:00',
                    ],
                    [
                        'novelty_type_code' => 'HEDI', // because of check_in_novelty_type_code
                        'sub_cost_center_id' => 1,
                        'start_at' => '2019-04-03 06:00:00',
                        'end_at' => '2019-04-03 06:59:59',
                    ],
                    [
                        'novelty_type_code' => 'HADI', // because of check_out_novelty_type_code
                        'sub_cost_center_id' => 2,
                        'start_at' => '2019-04-03 18:00:01',
                        'end_at' => '2019-04-03 19:00:00',
                    ],
                ],
            ],
            [
                'wantTo' => 'test-17',
                'timeClockLog' => [
                    'work_shift_name' => '7-17',
                    'checked_in_at' => '2019-04-01 07:00:00', // on time
                    'checked_out_at' => '2019-04-01 12:30:00', // on time
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'start_at' => '2019-04-01 07:00:00',
                        'end_at' => '2019-04-01 12:30:00',
                    ],
                ],
            ],
            [
                'wantTo' => 'test-18',
                'timeClockLog' => [
                    'work_shift_name' => '7-17',
                    'checked_in_at' => '2019-04-01 13:30:00', // on time
                    'checked_out_at' => '2019-04-01 17:00:00', // on time
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'start_at' => '2019-04-01 13:30:00',
                        'end_at' => '2019-04-01 17:00:00',
                    ],
                ],
            ],
            [
                'wantTo' => 'test-19',
                'timeClockLog' => [
                    'work_shift_name' => '7-17',
                    'check_out_novelty_type_code' => 'HEDI', // additional time
                    'checked_in_at' => '2019-04-01 13:30:00', // on time
                    'checked_out_at' => '2019-04-01 19:00:00', // 2 hours late
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'start_at' => '2019-04-01 13:30:00',
                        'end_at' => '2019-04-01 17:00:00',
                    ],
                    [
                        'novelty_type_code' => 'HEDI',
                        'start_at' => '2019-04-01 17:00:01',
                        'end_at' => '2019-04-01 19:00:00',
                    ],
                ],
            ],
            [
                'wantTo' => 'test-20',
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'check_in_novelty_type_code' => 'PP', // personal permission
                    'checked_in_at' => '2019-04-01 08:00:00', // 1 hour late
                    'checked_out_at' => '2019-04-01 18:00:00', // on time
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        // 10 hours (from 8am to 6pm), minimum minutes to subtract launch time not reached
                        'start_at' => '2019-04-01 08:00:00',
                        'end_at' => '2019-04-01 18:00:00',
                    ],
                    [
                        'novelty_type_code' => 'PP',
                        'start_at' => '2019-04-01 07:00:00',
                        'end_at' => '2019-04-01 07:59:59',
                    ],
                ],
            ],
            [
                'wantTo' => 'test-21',
                'timeClockLog' => [
                    'work_shift_name' => '7-17',
                    'check_in_novelty_type_code' => 'PP', // personal permission
                    'checked_in_at' => '2019-04-01 08:00:00', // 1 hour late
                    'checked_out_at' => '2019-04-01 12:30:00', // on time
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        // 4.5 hours (from 8am to 12:30pm)
                        'start_at' => '2019-04-01 08:00:00',
                        'end_at' => '2019-04-01 12:30:00',
                    ],
                    [
                        'novelty_type_code' => 'PP',
                        'start_at' => '2019-04-01 07:00:00',
                        'end_at' => '2019-04-01 07:59:59',
                    ],
                ],
            ],
            [
                'wantTo' => 'test-22',
                'timeClockLog' => [
                    'work_shift_name' => '7-17',
                    'check_out_novelty_type_code' => 'PP', // personal permission
                    'checked_in_at' => '2019-04-01 07:00:00', // on time
                    'checked_out_at' => '2019-04-01 11:30:00', // 1 hour early
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        // 4.5 hours (from 8am to 12:30pm)
                        'start_at' => '2019-04-01 07:00:00',
                        'end_at' => '2019-04-01 11:30:00',
                    ],
                    [
                        'novelty_type_code' => 'PP',
                        'start_at' => '2019-04-01 11:30:01',
                        'end_at' => '2019-04-01 12:30:00',
                    ],
                ],
            ],
            [
                'wantTo' => 'test-23',
                'timeClockLog' => [ // time clock log without work shift
                    'check_in_novelty_type_code' => 'HADI', // additional time
                    'checked_in_at' => '2019-04-01 07:00:00', // time doesn't matters because work shift is null
                    'checked_out_at' => '2019-04-01 14:00:00', // time doesn't matters because work shift is null
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HADI',
                        'start_at' => '2019-04-01 07:00:00',
                        'end_at' => '2019-04-01 14:00:00',
                    ],
                ],
            ],
            [
                'wantTo' => 'test-24',
                'timeClockLog' => [ // time clock log with night work shift
                    'work_shift_name' => '22-6',
                    'checked_in_at' => '2019-04-01 22:00:00', // on time
                    'checked_out_at' => '2019-04-02 06:00:00', // on time
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'RECNO',
                        'start_at' => '2019-04-01 22:00:00',
                        'end_at' => '2019-04-02 05:59:59',
                    ],
                ],
            ],
            [
                'wantTo' => 'test-25',
                'timeClockLog' => [ // time clock log with night work shift
                    'work_shift_name' => '22-6',
                    'checked_in_at' => '2019-06-30 22:00:00', // sunday holiday, on time
                    'checked_out_at' => '2019-07-01 06:00:00', // test monday holiday, on time
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HNF',
                        'start_at' => '2019-06-30 22:00:00',
                        'end_at' => '2019-07-01 05:59:59',
                    ],
                ],
            ],
            [
                'wantTo' => 'test-26',
                'timeClockLog' => [ // time clock log with night work shift and one holiday
                    'work_shift_name' => '22-6',
                    'checked_in_at' => '2019-03-30 22:00:00', // saturday, on time
                    'checked_out_at' => '2019-03-31 06:00:00', // sunday holiday, on time
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'RECNO',
                        'start_at' => '2019-03-30 22:00:00',
                        'end_at' => '2019-03-30 23:59:59',
                    ],
                    [
                        'novelty_type_code' => 'HNF',
                        'start_at' => '2019-03-31 00:00:00',
                        'end_at' => '2019-03-31 05:59:59',
                    ],
                ],
            ],
            [
                'wantTo' => 'test-27',
                'timeClockLog' => [ // time clock log with one holiday and night work shift
                    'work_shift_name' => '22-6',
                    'checked_in_at' => '2019-07-01 22:00:00', // monday holiday, on time
                    'checked_out_at' => '2019-07-02 06:00:00', // tuesday work day, on time
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'RECNO',
                        'start_at' => '2019-07-02 00:00:00',
                        'end_at' => '2019-07-02 05:59:59',
                    ],
                    [
                        'novelty_type_code' => 'HNF',
                        'start_at' => '2019-07-01 22:00:00',
                        'end_at' => '2019-07-01 23:59:59',
                    ],
                ],
            ],
            [
                'wantTo' => 'test-28',
                'timeClockLog' => [ // time clock log on work day
                    'work_shift_name' => '14-22',
                    'checked_in_at' => '2019-04-01 14:00:00', // monday work day, on time
                    'checked_out_at' => '2019-04-01 22:00:00', // monday work day, on time
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'RECNO',
                        'start_at' => '2019-04-01 21:00:01',
                        'end_at' => '2019-04-01 22:00:00',
                    ],
                    [
                        'novelty_type_code' => 'HN',
                        'start_at' => '2019-04-01 14:00:00',
                        'end_at' => '2019-04-01 21:00:00',
                    ],
                ],
            ],
            [
                'wantTo' => 'test-29',
                'timeClockLog' => [ // time clock log on holiday
                    'work_shift_name' => '14-22',
                    'checked_in_at' => '2019-07-01 14:00:00', // monday holiday, on time
                    'checked_out_at' => '2019-07-01 22:00:00', // monday holiday, on time
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HNF',
                        'start_at' => '2019-07-01 21:00:01',
                        'end_at' => '2019-07-01 22:00:00',
                    ],
                    [
                        'novelty_type_code' => 'HDF',
                        'start_at' => '2019-07-01 14:00:00',
                        'end_at' => '2019-07-01 21:00:00',
                    ],
                ],
            ],
            [
                'wantTo' => 'test-30',
                'timeClockLog' => [ // time clock log on workday
                    'work_shift_name' => '6-14',
                    'check_in_novelty_type_code' => 'HADI',
                    'checked_in_at' => '2019-04-01 05:00:00', // workday, one hour early
                    'checked_out_at' => '2019-04-01 14:00:00', // workday, on time
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'start_at' => '2019-04-01 06:00:00',
                        'end_at' => '2019-04-01 14:00:00',
                    ],
                    [
                        'novelty_type_code' => 'HADI',
                        'start_at' => '2019-04-01 05:00:00',
                        'end_at' => '2019-04-01 05:59:59',
                    ],
                ],
            ],
            // ################################################################ #
            //     Time lock logs with too late check in or early check out    #
            // ################################################################ #
            [
                'wantTo' => 'test-31',
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'check_in_novelty_type_code' => null, // empty novelty type
                    'checked_in_at' => '2019-04-01 08:00:00', // 1 hour late
                    'checked_out_at' => '2019-04-01 18:00:00', // on time
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        // 10 hours (from 8am to 6pm), minimum minutes to subtract launch time not reached
                        'start_at' => '2019-04-01 08:00:00',
                        'end_at' => '2019-04-01 18:00:00',
                    ],
                    [
                        'novelty_type_code' => 'PP', // default novelty type when check_in_novelty_type_id is null
                        'start_at' => '2019-04-01 07:00:00',
                        'end_at' => '2019-04-01 07:59:59',
                    ],
                ],
            ],
            // ################################################################ #
            //               Time lock logs with scheduled novelties           #
            // ################################################################ #
            [
                'wantTo' => 'test-32',
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'checked_in_at' => '2019-04-01 09:00:00', // too late, because of scheduled novelty
                    'checked_out_at' => '2019-04-01 18:00:00', // on time
                    'sub_cost_center_id' => 1,
                    'check_in_novelty_type_code' => 'PP', // novelty for too late check in
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [
                    [
                        'novelty_type_code' => 'CM',
                        'start_at' => '2019-04-01 07:00:00',
                        'end_at' => '2019-04-01 08:00:00', // this would be the expected time to check in
                    ],
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'CM', // this novelty should be now attached to time clock log record
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                    ],
                    [
                        'novelty_type_code' => 'HN',
                        'start_at' => '2019-04-01 09:00:00',
                        'end_at' => '2019-04-01 18:00:00',
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                    ],
                    [
                        'novelty_type_code' => 'PP', // novelty for too late check in
                        'sub_cost_center_id' => 1,
                        'start_at' => '2019-04-01 08:00:01',
                        'end_at' => '2019-04-01 08:59:59',
                    ],
                ],
            ],
            [
                'wantTo' => 'test-33',
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'sub_cost_center_id' => 1,
                    'check_out_novelty_type_code' => null, // empty novelty type
                    'checked_in_at' => '2019-04-01 07:00:00', // on time
                    'checked_out_at' => '2019-04-01 16:00:00', // on time, because of scheduled novelty
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [
                    [
                        'novelty_type_code' => 'CM', // scheduled novelty for check out
                        'start_at' => '2019-04-01 16:00:00',
                        'end_at' => '2019-04-01 18:00:00',
                    ],
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        // 9 hours (from 7am to 4pm), minimum minutes to subtract launch time not reached
                        'start_at' => '2019-04-01 07:00:00',
                        'end_at' => '2019-04-01 15:59:59',
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                    ],
                    [
                        'novelty_type_code' => 'CM', // this novelty should be now attached to time clock log record
                        'start_at' => '2019-04-01 16:00:00',
                        'end_at' => '2019-04-01 18:00:00',
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                    ],
                ],
            ],
            [
                'wantTo' => 'test-34',
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'check_out_novelty_type_code' => null, // empty novelty type
                    'checked_in_at' => '2019-04-01 09:00:00', // too late, because of scheduled novelty
                    'checked_out_at' => '2019-04-01 16:00:00', // too early, because of scheduled novelty
                    'sub_cost_center_id' => 1,
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [
                    [
                        'novelty_type_code' => 'CM', // scheduled novelty for check in
                        'start_at' => '2019-04-01 07:00:00',
                        'end_at' => '2019-04-01 08:00:00',
                    ],
                    [
                        'novelty_type_code' => 'CM', // scheduled novelty for check out
                        'start_at' => '2019-04-01 17:00:00',
                        'end_at' => '2019-04-01 18:00:00',
                    ],
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'CM', // this novelty should be now attached to time clock log record
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                        'start_at' => '2019-04-01 07:00:00',
                        'end_at' => '2019-04-01 08:00:00',
                    ],
                    [
                        'novelty_type_code' => 'CM', // this novelty should be now attached to time clock log record
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                        'start_at' => '2019-04-01 17:00:00',
                        'end_at' => '2019-04-01 18:00:00',
                    ],
                    [
                        'novelty_type_code' => 'HN',
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                        'start_at' => '2019-04-01 09:00:00',
                        'end_at' => '2019-04-01 16:00:00',
                    ],
                    [
                        'novelty_type_code' => 'PP', // novelty for early check out
                        'start_at' => '2019-04-01 08:00:01',
                        'end_at' => '2019-04-01 08:59:59',
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                    ],
                    [
                        'novelty_type_code' => 'PP', // novelty for late check out
                        'start_at' => '2019-04-01 16:00:01',
                        'end_at' => '2019-04-01 16:59:59',
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                    ],
                ],
            ],
            [
                'wantTo' => 'test-35',
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'check_in_novelty_type_code' => 'PP', // because check in is 1 hour late
                    'checked_in_at' => '2019-04-01 08:00:00', // too late, because work shift
                    'checked_out_at' => '2019-04-01 10:00:00', // on time, because scheduled novelty
                    'sub_cost_center_id' => 1,
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [
                    [
                        'novelty_type_code' => 'CM', // scheduled novelty for check in
                        'start_at' => '2019-04-01 10:00:00',
                        'end_at' => '2019-04-01 11:00:00',
                    ],
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'start_at' => '2019-04-01 08:00:00',
                        'end_at' => '2019-04-01 09:59:59',
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                    ],
                    [
                        'novelty_type_code' => 'CM',
                        'sub_cost_center_id' => 1,
                        'start_at' => '2019-04-01 10:00:00',
                        'end_at' => '2019-04-01 11:00:00',
                    ],
                    [
                        'novelty_type_code' => 'PP', // novelty for late check in and early check out
                        'start_at' => '2019-04-01 07:00:00',
                        'end_at' => '2019-04-01 07:59:59',
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                    ],
                ],
            ],
            [
                'wantTo' => 'test-36',
                'timeClockLog' => [ // time clock log on sunday
                    'work_shift_name' => '14-22 Sundays',
                    'checked_in_at' => '2019-07-21 16:00:00', // sunday, two hours late
                    'checked_out_at' => '2019-07-21 17:00:00', // sunday, five hours early
                    'check_in_novelty_type_code' => 'PP', // for the start time not worked
                    'check_out_novelty_type_code' => 'PP', // for the final time not worked
                    'sub_cost_center_id' => 1,
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'PP',
                        'start_at' => '2019-07-21 14:00:00',
                        'end_at' => '2019-07-21 15:59:59',
                        'sub_cost_center_id' => 1,
                    ],
                    [
                        'novelty_type_code' => 'PP',
                        'start_at' => '2019-07-21 17:00:01',
                        'end_at' => '2019-07-21 22:00:00',
                        'sub_cost_center_id' => 1,
                    ],
                    [
                        'novelty_type_code' => 'HDF',
                        'start_at' => '2019-07-21 16:00:00',
                        'end_at' => '2019-07-21 17:00:00',
                        'sub_cost_center_id' => 1,
                    ],
                ],
            ],
            [
                'wantTo' => 'test-37',
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'check_out_novelty_type_code' => null, // empty novelty type
                    'checked_in_at' => '2019-04-01 07:00:00', // on time
                    'checked_out_at' => '2019-04-01 17:00:00', // one hour early
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'start_at' => '2019-04-01 07:00:00',
                        'end_at' => '2019-04-01 17:00:00',
                    ],
                    [
                        'novelty_type_code' => 'PP', // default novelty type when check_in_novelty_type_id is null
                        'start_at' => '2019-04-01 17:00:01',
                        'end_at' => '2019-04-01 18:00:00',
                    ],
                ],
            ],
            [
                'wantTo' => 'test-38',
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'sub_cost_center_id' => 1,
                    'check_out_novelty_type_code' => null, // empty novelty type
                    'checked_in_at' => '2019-04-01 08:00:00', // on time, because of scheduled novelty
                    'checked_out_at' => '2019-04-01 18:00:00', // on time
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [
                    [
                        'novelty_type_code' => 'CM', // scheduled novelty for check in
                        'start_at' => '2019-04-01 07:00:00',
                        'end_at' => '2019-04-01 08:00:00',
                    ],
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'start_at' => '2019-04-01 08:00:01',
                        'end_at' => '2019-04-01 18:00:00',
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                    ],
                    [
                        'novelty_type_code' => 'CM', // this novelty should be now attached to time clock log record
                        'start_at' => '2019-04-01 07:00:00',
                        'end_at' => '2019-04-01 08:00:00',
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                    ],
                ],
            ],
            [
                'wantTo' => 'test-39',
                'timeClockLog' => [
                    'work_shift_name' => '7-18',
                    'check_out_novelty_type_code' => null, // empty novelty type
                    'checked_in_at' => '2019-04-01 09:00:00', // on time, because of scheduled novelty
                    'checked_out_at' => '2019-04-01 16:00:00', // on time, because of scheduled novelty
                    'sub_cost_center_id' => 1,
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [
                    [
                        'novelty_type_code' => 'CM', // scheduled novelty for check in
                        'start_at' => '2019-04-01 07:00:00',
                        'end_at' => '2019-04-01 09:00:00',
                    ],
                    [
                        'novelty_type_code' => 'CM', // scheduled novelty for check out
                        'start_at' => '2019-04-01 16:00:00',
                        'end_at' => '2019-04-01 18:00:00',
                    ],
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'start_at' => '2019-04-01 09:00:01',
                        'end_at' => '2019-04-01 15:59:59',
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                    ],
                    [
                        'novelty_type_code' => 'CM', // this novelty should be now attached to time clock log record
                        'start_at' => '2019-04-01 07:00:00',
                        'end_at' => '2019-04-01 09:00:00',
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                    ],
                    [
                        'novelty_type_code' => 'CM', // this novelty should be now attached to time clock log record
                        'start_at' => '2019-04-01 16:00:00',
                        'end_at' => '2019-04-01 18:00:00',
                        'sub_cost_center_id' => 1, // should be attached to time clock log sub cost center
                    ],
                ],
            ],
            [ // time zone on this scenario is UTC but work shift is America/Bogota!!
                'wantTo' => 'test-40',
                'timeClockLog' => [
                    'work_shift_name' => '6-14 America/Bogota', // work shift with non UTC time zone
                    'sub_cost_center_id' => 1,
                    'check_out_novelty_type_code' => null, // empty novelty type
                    'checked_in_at' => '2019-04-01 11:00:00', // on time, because work shift
                    'checked_out_at' => '2019-04-01 15:00:00', // on time, because scheduled novelty
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [
                    [
                        'novelty_type_code' => 'CM', // scheduled novelty for check out
                        'start_at' => '2019-04-01 15:00:00',
                        'end_at' => '2019-04-01 16:00:00',
                    ],
                ],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HN',
                        'start_at' => '2019-04-01 11:00:00',
                        'end_at' => '2019-04-01 14:59:59',
                        'sub_cost_center_id' => 1, // from time clock log sub cost center id
                    ],
                    [
                        'novelty_type_code' => 'CM', // this novelty should be now attached to time clock log record
                        'start_at' => '2019-04-01 15:00:00',
                        'end_at' => '2019-04-01 16:00:00',
                        'sub_cost_center_id' => 1, // from time clock log sub cost center id
                    ],
                ],
            ],
            [
                'wantTo' => 'test-41',
                'timeClockLog' => [ // time clock log on workday
                    'work_shift_name' => '14-22',
                    'checked_in_at' => '2019-04-01 12:00:00', // workday, two hours early
                    'checked_out_at' => '2019-04-01 13:30:00', // workday, two hours early, before shift start
                    'check_in_novelty_type_code' => 'HADI',
                    'check_in_sub_cost_center_id' => 2,
                    'sub_cost_center_id' => 1,
                    'check_out_novelty_type_code' => 'PP', // for the time not worked, the entire work shift
                ],
                'expectedOutPut' => true,
                'scheduledNovelties' => [],
                'createdNovelties' => [
                    [
                        'novelty_type_code' => 'HADI',
                        'sub_cost_center_id' => 2,
                        'start_at' => '2019-04-01 12:00:00',
                        'end_at' => '2019-04-01 13:30:00',
                    ],
                    [
                        'novelty_type_code' => 'PP',
                        'sub_cost_center_id' => 1,
                        'start_at' => '2019-04-01 14:00:00',
                        'end_at' => '2019-04-01 22:00:00',
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider successCases
     */
    public function testToRunAction($_, $timeClockLogData, $expectedOutPut, $scheduledNovelties, $createdNovelties)
    {
        $timeClockData = $this->mapTimeClockData($timeClockLogData);
        $timeClockLog = factory(TimeClockLog::class)->create($timeClockData);

        // create scheduled novelties
        foreach ($scheduledNovelties as $scheduledNovelty) {
            $noveltyType = $this->noveltyTypes->firstWhere('code', $scheduledNovelty['novelty_type_code']);

            factory(Novelty::class)->create([
                'employee_id' => $timeClockLog->employee->id,
                'novelty_type_id' => $noveltyType->id,
                'start_at' => $scheduledNovelty['start_at'],
                'end_at' => $scheduledNovelty['end_at'],
                'time_clock_log_id' => null,
            ]);
        }

        $action = app(RegisterTimeClockNoveltiesAction::class);
        $this->assertEquals($expectedOutPut, $action->run($timeClockLog->id));

        $this->assertDatabaseRecordsCount(count($createdNovelties), 'novelties', [
            'time_clock_log_id' => $timeClockLog->id,
            'employee_id' => $timeClockLog->employee_id,
        ]);

        foreach ($createdNovelties as $novelty) {
            $noveltyType = $this->noveltyTypes->firstWhere('code', $novelty['novelty_type_code']);
            $times = array_filter([
                'start_at' => $novelty['start_at'] ?? null,
                'end_at' => $novelty['end_at'] ?? null,
            ]);

            $this->assertDatabaseHas('novelties', $times + [
                'time_clock_log_id' => $timeClockLog->id,
                'employee_id' => $timeClockLog->employee->id,
                'novelty_type_id' => $noveltyType->id,
                'sub_cost_center_id' => $novelty['sub_cost_center_id'] ?? null,
            ]);
        }
    }

    /**
     * Map time clock provider data to be used on Laravel factory.
     *
     * @param  array  $timeClock
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
     */
    public function whenHasLateCheckOutWithNoveltyOnMorningAndCheckInAgainOnAfternoon()
    {
        Carbon::setTestNow(Carbon::parse('2019-04-01')); // monday workday

        // morning log with check out addition novelty due to late check out
        $morningLog = factory(TimeClockLog::class)->create([
            'work_shift_id' => $this->workShifts->firstWhere('name', '7-12 13:30-17:00')->id,
            'checked_in_at' => now()->setTime(06, 58),
            'checked_out_at' => now()->setTime(12, 30),
            'check_out_novelty_type_id' => $this->noveltyTypes->firstWhere('code', 'HADI')->id,
            'check_out_sub_cost_center_id' => $this->subCostCenters->first()->id,
        ]);

        factory(Novelty::class)->create([
            'employee_id' => $morningLog->employee_id,
            'time_clock_log_id' => $morningLog->id,
            'novelty_type_id' => $this->noveltyTypes->firstWhere('code', 'HN')->id,
            'start_at' => now()->setTime(07, 00),
            'end_at' => now()->setTime(12, 00),
        ]);

        factory(Novelty::class)->create([
            'employee_id' => $morningLog->employee_id,
            'time_clock_log_id' => $morningLog->id,
            'novelty_type_id' => $this->noveltyTypes->firstWhere('code', 'HADI')->id,
            'start_at' => now()->setTime(12, 00),
            'end_at' => now()->setTime(12, 30),
        ]);

        // afternoon log with late check in
        $afternoonLog = factory(TimeClockLog::class)->create([
            'work_shift_id' => $this->workShifts->firstWhere('name', '7-12 13:30-17:00')->id,
            'employee_id' => $morningLog->employee_id,
            'checked_in_at' => now()->setTime(14, 30), // late check in, 1 hour late
            'checked_out_at' => now()->setTime(17, 30), // late check out, 0.5 hours late
            'check_in_novelty_type_id' => $this->noveltyTypes->firstWhere('code', 'PP')->id,
            'check_out_novelty_type_id' => $this->noveltyTypes->firstWhere('code', 'HADI')->id,
            'check_out_sub_cost_center_id' => $this->subCostCenters->first()->id,
        ]);

        $action = app(RegisterTimeClockNoveltiesAction::class);
        $result = $action->run($afternoonLog->id);

        $this->assertTrue($result);

        $this->assertDatabaseHas('novelties', [ // ordinary time
            'time_clock_log_id' => $afternoonLog->id,
            'novelty_type_id' => $this->noveltyTypes->where('code', 'HN')->first()->id,
            'start_at' => '2019-04-01 14:30:00',
            'end_at' => '2019-04-01 17:00:00',
        ]);

        $this->assertDatabaseHas('novelties', [ // missing time
            'time_clock_log_id' => $afternoonLog->id,
            'novelty_type_id' => $this->noveltyTypes->where('code', 'PP')->first()->id,
            'start_at' => '2019-04-01 13:30:00',
            'end_at' => '2019-04-01 14:29:59',
        ]);

        $this->assertDatabaseHas('novelties', [ // additional time
            'time_clock_log_id' => $afternoonLog->id,
            'novelty_type_id' => $this->noveltyTypes->where('code', 'HADI')->first()->id,
            'start_at' => '2019-04-01 17:00:01',
            'end_at' => '2019-04-01 17:30:00',
        ]);

        $this->assertDatabaseRecordsCount(3, 'novelties', ['time_clock_log_id' => $afternoonLog->id]);
    }

    /**
     * @test
     */
    public function shouldBeAwareFromPreviousClockedTimeOnSameWorkShift()
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

        // scheduled novelty for morning log
        factory(Novelty::class)->create([
            'employee_id' => $morningLog->employee_id,
            'time_clock_log_id' => $morningLog->id,
            'novelty_type_id' => $this->noveltyTypes->where('code', 'PP')->first()->id,
            'start_at' => now()->setTime(12, 00),
            'end_at' => now()->setTime(14, 00), // next check in should be at 2pm
        ]);

        factory(Novelty::class)->create([
            'employee_id' => $morningLog->employee_id,
            'time_clock_log_id' => $morningLog->id,
            'novelty_type_id' => $this->noveltyTypes->where('code', 'HN')->first()->id,
            'start_at' => now()->setTime(07, 00),
            'end_at' => now()->setTime(12, 00),
        ]);

        // afternoon log, after scheduled novelty
        $afternoonLog = factory(TimeClockLog::class)->create([
            'work_shift_id' => $this->workShifts->where('name', '7-18')->first()->id,
            'employee_id' => $morningLog->employee_id,
            'checked_in_at' => now()->setTime(14, 00), // on time, because past scheduled novelty
            'checked_out_at' => now()->setTime(18, 30), // late check out, 0.5 hours late
            'check_in_novelty_type_id' => null,
            'check_out_novelty_type_id' => $this->noveltyTypes->where('code', 'HADI')->first()->id,
            'check_out_sub_cost_center_id' => $this->subCostCenters->first()->id,
        ]);

        $action = app(RegisterTimeClockNoveltiesAction::class);
        $result = $action->run($afternoonLog->id);

        $this->assertTrue($result);

        $this->assertDatabaseHas('novelties', [ // ordinary time
            'time_clock_log_id' => $afternoonLog->id,
            'novelty_type_id' => $this->noveltyTypes->where('code', 'HN')->first()->id,
            'start_at' => '2019-04-01 14:00:01',
            'end_at' => '2019-04-01 18:00:00',
        ]);

        $this->assertDatabaseHas('novelties', [ // additional time
            'time_clock_log_id' => $afternoonLog->id,
            'novelty_type_id' => $this->noveltyTypes->where('code', 'HADI')->first()->id,
            'start_at' => '2019-04-01 18:00:01',
            'end_at' => '2019-04-01 18:30:00',
        ]);

        $this->assertDatabaseRecordsCount(2, 'novelties', ['time_clock_log_id' => $afternoonLog->id]);
    }

    /**
     * @todo set a proper name to this test case
     * @test
     */
    public function foo()
    {
        $workShift = factory(WorkShift::class)->create([
            'name' => '7-15:30',
            'grace_minutes_after_end_times' => 30,
            'grace_minutes_after_start_times' => 30,
            'grace_minutes_before_end_times' => 20,
            'grace_minutes_before_start_times' => 30,
            'meal_time_in_minutes' => 0,
            'min_minutes_required_to_discount_meal_time' => 0,
            'applies_on_days' => [1, 2, 3, 4, 5],
            'time_slots' => [['end' => '15:30', 'start' => '07:00']],
            'time_zone' => 'America/Bogota',
        ]);

        // morning log with attached addition novelty due to late check out
        $morningLog = factory(TimeClockLog::class)->create([
            'work_shift_id' => $workShift->id,
            'checked_in_at' => '2020-07-01 11:06:00', // wednesday workday
            'checked_out_at' => '2020-07-01 16:36:00',
            'check_out_novelty_type_id' => null,
            'check_in_novelty_type_id' => $this->noveltyTypes->firstWhere('code', 'HADI')->id,
            'check_out_sub_cost_center_id' => $this->subCostCenters->first()->id,
        ]);

        // scheduled novelty for morning log
        factory(Novelty::class)->create([
            'employee_id' => $morningLog->employee_id,
            'time_clock_log_id' => $morningLog->id,
            'novelty_type_id' => $this->noveltyTypes->firstWhere('code', 'PP')->id,
            'start_at' => '2020-07-01 16:36:00',
            'end_at' => '2020-07-01 18:15:00', // next check in on this datetime
        ]);

        factory(Novelty::class)->create([
            'employee_id' => $morningLog->employee_id,
            'time_clock_log_id' => $morningLog->id,
            'novelty_type_id' => $this->noveltyTypes->firstWhere('code', 'HADI')->id,
            'start_at' => '2020-07-01 11:06:00',
            'end_at' => '2020-07-01 11:59:59',
        ]);

        factory(Novelty::class)->create([
            'employee_id' => $morningLog->employee_id,
            'time_clock_log_id' => $morningLog->id,
            'novelty_type_id' => $this->noveltyTypes->firstWhere('code', 'HN')->id,
            'start_at' => '2020-07-01 12:00:00',
            'end_at' => '2020-07-01 16:36:00',
        ]);

        // afternoon log, after scheduled novelty
        $afternoonLog = factory(TimeClockLog::class)->create([
            'work_shift_id' => $workShift->id,
            'employee_id' => $morningLog->employee_id,
            'checked_in_at' => '2020-07-01 18:15:00', // on time because scheduled novelty
            'checked_out_at' => '2020-07-01 20:41:00', // on time because work shift grace time
            'check_in_novelty_type_id' => null,
            'check_out_novelty_type_id' => null,
            'check_out_sub_cost_center_id' => $this->subCostCenters->first()->id,
        ]);

        $action = app(RegisterTimeClockNoveltiesAction::class);
        $result = $action->run($afternoonLog->id);

        $this->assertTrue($result);

        $this->assertDatabaseHas('novelties', [ // ordinary time
            'time_clock_log_id' => $afternoonLog->id,
            'novelty_type_id' => $this->noveltyTypes->where('code', 'HN')->first()->id,
            'start_at' => '2020-07-01 18:15:01', // afternoon check in time
            'end_at' => '2020-07-01 20:30:00', // work shift end
        ]);

        $this->assertDatabaseRecordsCount(1, 'novelties', ['time_clock_log_id' => $afternoonLog->id]);
    }

    /**
     * @test
     */
    public function shouldBeAwareOfDistinctWorkShiftTimeZones()
    {
        // default values on America/Bogota timezone
        $this->artisan('db:seed', ['--class' => DefaultWorkShiftsSeeder::class]);
        $this->artisan('db:seed', ['--class' => DefaultNoveltyTypesSeed::class]);

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

        $this->assertDatabaseHas('novelties', [
            'time_clock_log_id' => $log->id,
            'novelty_type_id' => $noveltyTypes->firstWhere('code', 'HADI')->id,
            'start_at' => '2021-04-12 11:00:00',
            'end_at' => '2021-04-12 11:59:59',
        ]);

        $this->assertDatabaseHas('novelties', [
            'time_clock_log_id' => $log->id,
            'novelty_type_id' => $noveltyTypes->firstWhere('code', 'HN')->id,
            'start_at' => '2021-04-12 12:00:00',
            'end_at' => '2021-04-12 17:00:00',
        ]);

        $this->assertDatabaseHas('novelties', [
            'time_clock_log_id' => $log->id,
            'novelty_type_id' => $noveltyTypes->firstWhere('code', 'HADI')->id,
            'start_at' => '2021-04-12 17:00:01',
            'end_at' => '2021-04-12 17:59:59',
        ]);

        $this->assertDatabaseHas('novelties', [
            'time_clock_log_id' => $log->id,
            'novelty_type_id' => $noveltyTypes->firstWhere('code', 'HN')->id,
            'start_at' => '2021-04-12 18:00:00',
            'end_at' => '2021-04-12 19:00:00',
        ]);
    }

    /**
     * @test
     */
    public function shouldBeAwareOfDistinctWorkShiftTimeZonesWithHolidays()
    {
        // default values on America/Bogota timezone
        $this->artisan('db:seed', ['--class' => DefaultWorkShiftsSeeder::class]);
        $this->artisan('db:seed', ['--class' => DefaultNoveltyTypesSeed::class]);

        $noveltyTypes = NoveltyType::all();

        // data is stored in UTC
        $log = factory(TimeClockLog::class)->create([
            'sub_cost_center_id' => factory(SubCostCenter::class)->create()->id,
            'work_shift_id' => WorkShift::where('name', '22-06')->first()->id,
            'checked_in_at' => '2021-04-05 03:00:00', // 10pm sunday holiday in America/Bogota
            'checked_out_at' => '2021-04-05 13:00:00', // 8am monday workday in America/Bogota
            'check_out_novelty_type_id' => $noveltyTypes->firstWhere('code', 'HADI')->id,
            'check_out_sub_cost_center_id' => factory(SubCostCenter::class)->create()->id,
        ]);

        $action = app(RegisterTimeClockNoveltiesAction::class);
        $action->run($log->id);

        $this->assertDatabaseHas('novelties', [
            'time_clock_log_id' => $log->id,
            'novelty_type_id' => $noveltyTypes->firstWhere('code', 'HNF')->id,
            'start_at' => '2021-04-05 03:00:00',
            'end_at' => '2021-04-05 04:59:59',
        ]);

        $this->assertDatabaseHas('novelties', [
            'time_clock_log_id' => $log->id,
            'novelty_type_id' => $noveltyTypes->firstWhere('code', 'RECNO')->id,
            'start_at' => '2021-04-05 05:00:00',
            'end_at' => '2021-04-05 10:59:59',
        ]);

        $this->assertDatabaseHas('novelties', [
            'time_clock_log_id' => $log->id,
            'novelty_type_id' => $noveltyTypes->firstWhere('code', 'HADI')->id,
            'start_at' => '2021-04-05 11:00:01',
            'end_at' => '2021-04-05 13:00:00',
        ]);
    }

    /**
     * @test
     */
    public function shouldAddDefaultNoveltyForsubtractionWhenEmployeeCheckOutTooEarlyFromWorkShift()
    {
        // default values on America/Bogota timezone
        $this->artisan('db:seed', ['--class' => DefaultNoveltyTypesSeed::class]);
        $noveltyTypes = NoveltyType::all();

        $workShift = factory(WorkShift::class)->create([
            'name' => '7-15:30',
            'grace_minutes_before_start_times' => 30,
            'grace_minutes_after_start_times' => 30,
            'grace_minutes_before_end_times' => 20,
            'grace_minutes_after_end_times' => 30,
            'meal_time_in_minutes' => 0,
            'min_minutes_required_to_discount_meal_time' => 0,
            'applies_on_days' => [1, 2, 3, 4, 5],
            'time_zone' => 'America/Bogota',
            'time_slots' => [['end' => '15:30', 'start' => '07:00']],
        ]);

        // data is stored in UTC
        $log = factory(TimeClockLog::class)->create([
            'sub_cost_center_id' => factory(SubCostCenter::class)->create()->id,
            'work_shift_id' => $workShift->id,
            'checked_in_at' => now()->setDate(2020, 05, 20)->setTime(10, 56, 31),
            'checked_out_at' => now()->setDate(2020, 05, 20)->setTime(19, 38, 05),
            'check_out_novelty_type_id' => $noveltyTypes->firstWhere('code', 'PP')->id,
            'check_out_sub_cost_center_id' => factory(SubCostCenter::class)->create()->id,
        ]);

        $action = app(RegisterTimeClockNoveltiesAction::class);
        $action->run($log->id);

        $this->assertDatabaseHas('novelties', [
            'time_clock_log_id' => $log->id,
            'novelty_type_id' => $noveltyTypes->firstWhere('code', 'HN')->id,
            'start_at' => '2020-05-20 12:00:00',
            'end_at' => '2020-05-20 19:38:05',
        ]);

        $this->assertDatabaseHas('novelties', [
            'time_clock_log_id' => $log->id,
            'novelty_type_id' => $noveltyTypes->firstWhere('code', 'HADI')->id,
            'start_at' => '2020-05-20 10:56:31',
            'end_at' => '2020-05-20 11:59:59',
        ]);

        $this->assertDatabaseHas('novelties', [
            'time_clock_log_id' => $log->id,
            'novelty_type_id' => $noveltyTypes->firstWhere('code', 'PP')->id,
            'start_at' => '2020-05-20 19:38:06',
            'end_at' => '2020-05-20 20:30:00',
        ]);
    }

    /**
     * @test
     */
    public function shouldBeAwareOfExistingNoveltyTimeFromOtherTimeClockLogs()
    {
        $this->artisan('db:seed', ['--class' => DefaultNoveltyTypesSeed::class]);
        $noveltyTypes = NoveltyType::all();

        $workShift = factory(WorkShift::class)->create([
            'name' => '14:35-22:35',
            'grace_minutes_before_start_times' => 5,
            'grace_minutes_after_start_times' => 5,
            'grace_minutes_before_end_times' => 5,
            'grace_minutes_after_end_times' => 5,
            'meal_time_in_minutes' => 0,
            'min_minutes_required_to_discount_meal_time' => 0,
            'applies_on_days' => [1, 2, 3, 4, 5],
            'time_zone' => 'America/Bogota',
            'time_slots' => [['end' => '22:35', 'start' => '14:35']],
        ]);

        $employee = factory(Employee::class)->create();

        // first time clock log and novelties
        $firstTimeClockLog = factory(TimeClockLog::class)->create([
            'employee_id' => $employee,
            'work_shift_id' => $workShift,
            'sub_cost_center_id' => $this->subCostCenters->first(),
            'checked_in_at' => '2020-11-03 19:44:00',
            'expected_check_in_at' => '2020-11-03 19:35:00',
            'check_in_novelty_type_id' => $noveltyTypes->firstWhere('code', 'PP'),
            'checked_out_at' => '2020-11-03 20:08:00',
            'expected_check_out_at' => '2020-11-03 20:08:00',
        ]);

        factory(Novelty::class)->create([
            'employee_id' => $employee,
            'time_clock_log_id' => $firstTimeClockLog,
            'novelty_type_id' => $noveltyTypes->firstWhere('code', 'PP'),
            'start_at' => '2020-11-03 20:08:00', // scheduled novelty
            'end_at' => '2020-11-03 20:17:00',
            'comment' => 'test permissions novelty',
        ]);
        factory(Novelty::class)->create([
            'time_clock_log_id' => $firstTimeClockLog,
            'employee_id' => $employee,
            'novelty_type_id' => $noveltyTypes->firstWhere('code', 'HN'),
            'sub_cost_center_id' => $this->subCostCenters->first(),
            'start_at' => '2020-11-03 19:44:00', // work time
            'end_at' => '2020-11-03 20:07:59',
        ]);
        factory(Novelty::class)->create([
            'time_clock_log_id' => $firstTimeClockLog,
            'employee_id' => $employee,
            'novelty_type_id' => $noveltyTypes->firstWhere('code', 'PP'),
            'sub_cost_center_id' => $this->subCostCenters->first(),
            'start_at' => '2020-11-03 19:35:00', // too late checkout novelty
            'end_at' => '2020-11-03 19:43:59',
        ]);

        // last time clock log
        $lastTimeClock = factory(TimeClockLog::class)->create([
            'employee_id' => $employee,
            'sub_cost_center_id' => factory(SubCostCenter::class)->create()->id,
            'work_shift_id' => $workShift->id,
            'checked_in_at' => '2020-11-03 20:17:00',
            'checked_out_at' => '2020-11-03 20:25:00',
            'check_out_sub_cost_center_id' => factory(SubCostCenter::class)->create()->id,
        ]);

        $action = app(RegisterTimeClockNoveltiesAction::class);
        $action->run($lastTimeClock->id);

        $this->assertDatabaseRecordsCount(5, 'novelties', ['employee_id' => $employee->id]);
        $this->assertDatabaseRecordsCount(3, 'novelties', ['time_clock_log_id' => $firstTimeClockLog->id]);
        $this->assertDatabaseRecordsCount(2, 'novelties', ['time_clock_log_id' => $lastTimeClock->id]);
        // created novelties
        $this->assertDatabaseHas('novelties', [
            'time_clock_log_id' => $lastTimeClock->id,
            'novelty_type_id' => $noveltyTypes->firstWhere('code', 'HN')->id,
            'start_at' => '2020-11-03 20:17:01',
            'end_at' => '2020-11-03 20:25:00',
        ]);
        $this->assertDatabaseHas('novelties', [
            'time_clock_log_id' => $lastTimeClock->id,
            'novelty_type_id' => $noveltyTypes->firstWhere('code', 'PP')->id,
            'start_at' => '2020-11-03 20:25:01',
            'end_at' => '2020-11-04 03:35:00',
        ]);
        // first time clock novelties should not be changed
        $this->assertDatabaseHas('novelties', [
            'time_clock_log_id' => $firstTimeClockLog->id,
            'start_at' => '2020-11-03 20:08:00',
            'end_at' => '2020-11-03 20:17:00',
        ]);
        $this->assertDatabaseHas('novelties', [
            'time_clock_log_id' => $firstTimeClockLog->id,
            'start_at' => '2020-11-03 19:44:00',
            'end_at' => '2020-11-03 20:07:59',
        ]);
        $this->assertDatabaseHas('novelties', [
            'time_clock_log_id' => $firstTimeClockLog->id,
            'start_at' => '2020-11-03 19:35:00',
            'end_at' => '2020-11-03 19:43:59',
        ]);
    }
}
