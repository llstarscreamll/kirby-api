<?php

namespace ClockTime;

use Illuminate\Support\Facades\Artisan;
use Kirby\TimeClock\Events\TimeClockLogApprovalCreatedEvent;
use Kirby\TimeClock\Models\TimeClockLog;
use TimeClockPermissionsSeeder;

/**
 * Class CreateTimeClockLogApprovalTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateTimeClockLogApprovalTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/time-clock-logs/{time-clock-log-id}/approvals';

    /**
     * @var \Kirby\Users\Models\User
     */
    private $user;

    /**
     * @var \Illuminate\Support\Collection
     */
    private $timeClockLogs;

    public function setUp(): void
    {
        parent::setUp();
        Artisan::call('db:seed', ['--class' => TimeClockPermissionsSeeder::class]);
        $this->actingAsAdmin($this->user = factory(\Kirby\Users\Models\User::class)->create());
        $this->timeClockLogs = factory(TimeClockLog::class, 2)->create();

    }

    /**
     * @test
     */
    public function shouldSetApprovalSuccessfully()
    {
        $timeClockLog = $this->timeClockLogs->first();

        $this->expectsEvents(TimeClockLogApprovalCreatedEvent::class);

        $endpoint = str_replace('{time-clock-log-id}', $timeClockLog->id, $this->endpoint);
        $this->json('POST', $endpoint)
            ->assertCreated();

        $this->assertDatabaseHas('time_clock_log_approvals', [
            'user_id' => $this->user->id,
            'time_clock_log_id' => $timeClockLog->id,
        ]);
    }

    /**
     * @test
     */
    public function shouldReturnForbidenWhenUserDoesntHaveRequiredPermissions()
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();

        $endpoint = str_replace('{time-clock-log-id}', $this->timeClockLogs->first()->id, $this->endpoint);
        $this->json('POST', $endpoint)
            ->assertForbidden();

        $this->assertDatabaseMissing('time_clock_log_approvals', [
            'user_id' => $this->user->id,
            'time_clock_log_id' => $this->timeClockLogs->first()->id,
        ]);
    }

    /**
     * @test
     */
    public function shouldReturnNotFoundIfTimeClockLogDoesntExists()
    {
        $endpoint = str_replace('{time-clock-log-id}', 111, $this->endpoint);
        $this->json('POST', $endpoint)
            ->assertNotFound();
    }
}
