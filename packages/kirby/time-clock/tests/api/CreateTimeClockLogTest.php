<?php

namespace Kirby\TimeClock\Tests\api;

use Illuminate\Support\Facades\Artisan;
use Kirby\Company\Models\SubCostCenter;
use Kirby\Employees\Models\Identification;
use Kirby\TimeClock\Events\CheckedOutEvent;
use Kirby\TimeClock\Models\TimeClockLog;
use TimeClockPermissionsSeeder;

/**
 * Class CreateTimeClockLogTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateTimeClockLogTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/time-clock-logs';

    /**
     * @var \Kirby\Users\Models\User
     */
    private $user;

    public function setUp(): void
    {
        parent::setUp();
        Artisan::call('db:seed', ['--class' => TimeClockPermissionsSeeder::class]);

        $this->actingAsAdmin($this->user = factory(\Kirby\Users\Models\User::class)->create());
    }

    /**
     * @test
     */
    public function shouldCreateResourceSuccessful()
    {
        $timeClockLog = factory(TimeClockLog::class)->make([
            'sub_cost_center_id' => factory(SubCostCenter::class)->create()->id,
        ]);
        $timeClockLog->employee->identifications()->createMany(factory(Identification::class, 2)->make()->toArray());

        $timeClockLogData = $timeClockLog->toArray();
        $timeClockLogData['checked_in_at'] = $timeClockLog->checked_in_at->toIsoString();
        $timeClockLogData['checked_out_at'] = $timeClockLog->checked_out_at->toIsoString();

        unset(
            $timeClockLogData['checked_in_by_id'],
            $timeClockLogData['checked_out_by_id'],
            $timeClockLogData['expected_check_in_at'],
            $timeClockLogData['expected_check_out_at'],
            $timeClockLogData['employee'],
        );

        $this->expectsEvents(CheckedOutEvent::class);

        $this->json('POST', $this->endpoint, $timeClockLogData)->assertCreated();

        $timeClockLogData['checked_in_at'] = $timeClockLog->checked_in_at->toDateTimeString();
        $timeClockLogData['checked_out_at'] = $timeClockLog->checked_out_at->toDateTimeString();

        $this->assertDatabaseHas('time_clock_logs', $timeClockLogData);
    }

    /**
     * @test
     */
    public function shouldReturnForbiddenWhenUserDoesntHaveRequiredPermissions()
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();

        $this->json('POST', $this->endpoint, [])->assertForbidden();
    }
}
