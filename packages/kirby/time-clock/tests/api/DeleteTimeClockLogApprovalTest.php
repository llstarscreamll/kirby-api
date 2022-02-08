<?php

namespace Kirby\TimeClock\Tests\api;

use Illuminate\Support\Facades\Artisan;
use Kirby\TimeClock\Events\TimeClockLogApprovalDeletedEvent;
use Kirby\TimeClock\Models\TimeClockLog;
use TimeClockPermissionsSeeder;

/**
 * Class DeleteTimeClockLogApprovalTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class DeleteTimeClockLogApprovalTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/time-clock-logs/{time-clock-log-id}/approvals/{approval-id}';

    /**
     * @var \Kirby\Users\Models\User
     */
    private $user;

    /**
     * @var \Illuminate\Support\Collection
     */
    private $timeClockLogs;

    /**
     * @var string
     */
    private $approvalId;

    public function setUp(): void
    {
        parent::setUp();
        Artisan::call('db:seed', ['--class' => TimeClockPermissionsSeeder::class]);
        $this->actingAsAdmin($this->user = factory(\Kirby\Users\Models\User::class)->create());
        $this->timeClockLogs = factory(TimeClockLog::class, 2)->create();
        $this->approvalId = 1;
        $this->haveRecord('time_clock_log_approvals', [
            'user_id' => $this->user->id,
            'time_clock_log_id' => $this->timeClockLogs->first()->id,
        ]);
    }

    /**
     * @test
     */
    public function shouldDeleteApprovalSuccessfully()
    {
        $endpoint = str_replace(
            ['{time-clock-log-id}', '{approval-id}'],
            [$this->timeClockLogs->first()->id, $this->approvalId],
            $this->endpoint
        );

        $this->expectsEvents(TimeClockLogApprovalDeletedEvent::class);

        $this->json('DELETE', $endpoint)->assertOk();

        $this->assertDatabaseMissing('time_clock_log_approvals', [
            'user_id' => $this->user->id,
            'time_clock_log_id' => $this->timeClockLogs->first()->id,
        ]);
    }

    /**
     * @test
     */
    public function shouldReturnForbiddenWhenUserDoesntHaveRequiredPermissions()
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();

        $endpoint = str_replace(
            ['{time-clock-log-id}', '{approval-id}'],
            [$this->timeClockLogs->first()->id, $this->approvalId],
            $this->endpoint
        );

        $this->json('DELETE', $endpoint)->assertForbidden();

        $this->assertDatabaseHas('time_clock_log_approvals', [
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
        $this->json('DELETE', $endpoint)->assertNotFound();
    }
}
