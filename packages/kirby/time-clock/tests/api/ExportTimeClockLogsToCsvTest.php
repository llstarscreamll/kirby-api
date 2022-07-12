<?php

namespace Kirby\TimeClock\Tests\api;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Kirby\Authorization\Models\Permission;
use Kirby\TimeClock\Jobs\ExportTimeClockLogsJob;
use Kirby\TimeClock\Models\TimeClockLog;
use Symfony\Component\HttpFoundation\Response;
use TimeClockPermissionsSeeder;

/**
 * Class ExportTimeClockLogsToCsvTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 *
 * @internal
 */
class ExportTimeClockLogsToCsvTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/time-clock-logs/export';

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
    public function shouldReturnOkWhenUserHasPermissions()
    {
        $this->user->syncPermissions(Permission::where('name', 'time-clock-logs.global-search')->get());

        $this->json('GET', $this->endpoint, ['checkedInStart' => now()->subMonth()->toIsoString(), 'checkedInEnd' => now()->toIsoString()])
            ->assertOk()
            ->assertJsonPath('data', 'ok');
    }

    /**
     * @test
     */
    public function shouldDispatchJobWhenResponseIsOk()
    {
        $this->user->syncPermissions(Permission::where('name', 'time-clock-logs.global-search')->get());
        $payload = [
            'checkedInStart' => now()->subMonth()->toIsoString(),
            'checkedInEnd' => now()->toIsoString(),
        ];

        Queue::fake();

        $this->json('GET', $this->endpoint, $payload)
            ->assertOk();

        Queue::assertPushed(fn (ExportTimeClockLogsJob $job) => $job->userID = $this->user->id && $job->params === $payload);
    }

    /**
     * @test
     */
    public function shouldRequireCheckInDateRange()
    {
        $payload = [
            'checkedInStart' => null,
            'checkedInEnd' => null,
        ];

        $this->json('GET', $this->endpoint, $payload)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['checkedInStart', 'checkedInEnd']);
    }

    /**
     * @test
     */
    public function shouldNotAllowExportsGreaterThanSixMonths()
    {
        $payload = [
            'checkedInStart' => now()->subMonths(8)->toIsoString(),
            'checkedInEnd' => now()->toIsoString(),
        ];

        $this->json('GET', $this->endpoint, $payload)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['checkedInEnd']);
    }

    /**
     * @test
     */
    public function shouldReturnForbiddenWhenUserDoesntHaveRequiredPermissions()
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();

        $this->json('GET', $this->endpoint)->assertForbidden();
    }
}
