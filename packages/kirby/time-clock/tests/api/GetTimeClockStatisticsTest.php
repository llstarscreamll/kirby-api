<?php

namespace Kirby\TimeClock\Tests\api;

use Illuminate\Support\Facades\Artisan;
use Kirby\TimeClock\Models\TimeClockLog;
use TimeClockPermissionsSeeder;

/**
 * Class GetTimeClockStatisticsTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 *
 * @internal
 */
class GetTimeClockStatisticsTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/time-clock/statistics';

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
    public function shouldReturnCountOfPersonsInside()
    {
        factory(TimeClockLog::class, 2)->create();
        factory(TimeClockLog::class, 2)->create(['checked_out_at' => null]); // people inside

        $this->json('GET', $this->endpoint)
            ->assertOk()
            ->assertJsonPath('data.people_inside_count', 2);
    }

    /**
     * @test
     */
    public function shouldReturnForbiddenWhenUserDoesntHaveRequiredPermissions()
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();

        $this->json('GET', $this->endpoint, [])->assertForbidden();
    }
}
