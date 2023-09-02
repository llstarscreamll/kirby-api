<?php

namespace Kirby\TruckScale\Tests\Api\V1\Weighings;

use Kirby\TruckScale\Enums\WeighingStatus;
use Kirby\TruckScale\Models\Weighing;
use Kirby\Users\Models\User;
use Tests\TestCase;
use TruckScalePackageSeeder;

class CancelWeighingTest extends TestCase
{
    private $method = 'POST';
    private $path = 'api/1.0/weighings/{id}/cancel';

    /** @test */
    public function shouldCancelInProgressWeighing()
    {
        $this->seed(TruckScalePackageSeeder::class);
        $record = factory(Weighing::class)->create(['status' => WeighingStatus::InProgress]);

        $payload = ['comment' => 'test description'];

        $this->actingAsAdmin(factory(User::class)->create())
            ->json($this->method, str_replace('{id}', $record->id, $this->path), $payload)
            ->assertOk();

        $this->assertDatabaseHas('weighings', [
            'id' => $record->id,
            'status' => WeighingStatus::Canceled,
            'cancel_comment' => 'test description',
        ]);
    }

    /** @test */
    public function shouldNotUpdateTheCancelCommentIfWieghingIsAlreadyCanceled()
    {
        $this->seed(TruckScalePackageSeeder::class);
        $record = factory(Weighing::class)->create([
            'status' => WeighingStatus::Canceled,
            'cancel_comment' => 'foo',
        ]);

        $payload = ['comment' => 'test description'];

        $this->actingAsAdmin(factory(User::class)->create())
            ->json($this->method, str_replace('{id}', $record->id, $this->path), $payload)
            ->assertOk();

        $this->assertDatabaseHas('weighings', [
            'id' => $record->id,
            'status' => WeighingStatus::Canceled,
            'cancel_comment' => 'foo', // unchanged comment
        ]);
    }

    /** @test */
    public function shouldReturnErrorWhenCancelCommentIsEmpty()
    {
        $this->seed(TruckScalePackageSeeder::class);
        $record = factory(Weighing::class)->create(['status' => WeighingStatus::InProgress]);

        $payload = ['comment' => ''];

        $this->actingAsAdmin(factory(User::class)->create())
            ->json($this->method, str_replace('{id}', $record->id, $this->path), $payload)
            ->assertStatus(422);

        // status and comment should not be changed
        $this->assertDatabaseHas('weighings', [
            'id' => $record->id,
            'status' => WeighingStatus::InProgress,
            'cancel_comment' => '',
        ]);
    }

    /** @test */
    public function shouldReturnErrorWhenUserDoesNotHaveEnoughtPermissions()
    {
        $this->seed(TruckScalePackageSeeder::class);
        $record = factory(Weighing::class)->create(['status' => WeighingStatus::InProgress]);

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
