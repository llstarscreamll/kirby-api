<?php

namespace Kirby\TruckScale\Tests\Api\V1\Weighings;

use Kirby\TruckScale\Enums\WeighingStatus;
use Kirby\TruckScale\Models\Weighing;
use Kirby\Users\Models\User;
use Tests\TestCase;
use TruckScalePackageSeeder;

class ManualFinishWeighingTest extends TestCase
{
    private $method = 'POST';
    private $path = 'api/1.0/weighings/{id}/manual-finish';

    /** @test */
    public function shouldCancelInProgressWeighing()
    {
        $this->seed(TruckScalePackageSeeder::class);
        $record = factory(Weighing::class)->create(['status' => WeighingStatus::InProgress]);

        $this->actingAsAdmin(factory(User::class)->create())
            ->json($this->method, str_replace('{id}', $record->id, $this->path))
            ->assertOk();

        $this->assertDatabaseHas('weighings', [
            'id' => $record->id,
            'status' => WeighingStatus::ManualFinished,
        ]);
    }

    /** @test */
    public function shouldReturnErrorWhenUserDoesNotHaveEnoughPermissions()
    {
        $this->seed(TruckScalePackageSeeder::class);
        $record = factory(Weighing::class)->create(['status' => WeighingStatus::InProgress, 'cancel_comment' => '']);

        $payload = ['comment' => 'foo'];

        $this->actingAsGuest()
            ->json($this->method, str_replace('{id}', $record->id, $this->path), $payload)
            ->assertForbidden();

        // status and comment should not be changed
        $this->assertDatabaseHas('weighings', [
            'id' => $record->id,
            'status' => WeighingStatus::InProgress,
            'cancel_comment' => '',
        ]);
    }
}
