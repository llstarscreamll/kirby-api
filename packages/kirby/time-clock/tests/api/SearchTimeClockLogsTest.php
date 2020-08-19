<?php

namespace ClockTime;

use Illuminate\Support\Facades\Artisan;
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

    
    public function shouldReturnPaginatedData()
    {
        $this->json('GET',$this->endpoint)
            ->assertOk()
            ->assertJsonHasPath('data.0')
            ->assertJsonHasPath('data.1');
        // relations
        $this->assertJsonHasPath('data.1.novelties')
            ->assertJsonHasPath('data.1.employee.user')
            ->assertJsonHasPath('data.1.work_shift')
            ->assertJsonHasPath('data.1.approvals')
            ->assertJsonHasPath('data.1.sub_cost_center');
    }

    /**
     * @test
     
     */
    public function shouldReturnForbidenWhenUserDoesntHaveRequiredPermissions()
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();

        $this->json('GET',$this->endpoint, [])
            ->assertForbidden();
    }
}
