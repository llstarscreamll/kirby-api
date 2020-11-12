<?php

namespace Kirby\TimeClock\Tests\api;

use Illuminate\Support\Facades\Artisan;
use Kirby\Authorization\Models\Permission;
use Kirby\TimeClock\Models\TimeClockLog;
use TimeClockPermissionsSeeder;

/**
 * Class SearchTimeClockLogsTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SearchTimeClockLogsTest extends \Tests\TestCase
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
        // time clock logs
        factory(TimeClockLog::class, 2)->create();
    }

    /**
     * @test
     */
    public function shouldReturnDataFromAllEmployeesWhenDoesNotHaveEmployeePermission()
    {
        $this->user->syncPermissions(Permission::where('name', 'time-clock-logs.global-search')->get());

        $this->json('GET', $this->endpoint)
            ->assertOk()
            ->assertJsonHasPath('data.0')
            ->assertJsonHasPath('data.1')
            ->assertJsonHasPath('data.1.novelties') // relations
            ->assertJsonHasPath('data.1.employee.user')
            ->assertJsonHasPath('data.1.work_shift')
            ->assertJsonHasPath('data.1.approvals')
            ->assertJsonHasPath('data.1.sub_cost_center');
    }

    /**
     * @test
     */
    public function shouldReturnDataFromCurrentUserWhenHasEmployeeSearchAndNotGlobalSearchPermission()
    {
        // remove current user global search permission
        $this->user->syncPermissions(Permission::where('name', 'time-clock-logs.employee-search')->get());
        factory(TimeClockLog::class)->create(['employee_id' => $this->user->id]);

        $this->json('GET', $this->endpoint)
            ->assertOk()
            ->assertJsonCount(1, 'data') // current user logs only
            ->assertJsonHasPath('data.0.id', $this->user->id);
    }

    /**
     * @test
     */
    public function shouldReturnForbidenWhenUserDoesntHaveRequiredPermissions()
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();

        $this->json('GET', $this->endpoint, [])->assertForbidden();
    }
}
