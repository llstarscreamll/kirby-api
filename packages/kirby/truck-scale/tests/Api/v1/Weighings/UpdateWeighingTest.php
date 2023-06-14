<?php

namespace Kirby\TruckScale\Tests\Api\V1\Weighings;

use Kirby\TruckScale\Enums\VehicleType;
use Kirby\TruckScale\Enums\WeighingStatus;
use Kirby\TruckScale\Enums\WeighingType;
use Kirby\TruckScale\Models\Weighing;
use Kirby\Users\Models\User;
use Tests\TestCase;
use TruckScalePackageSeeder;

class UpdateWeighingTest extends TestCase
{
    private $method = 'PUT';
    private $path = 'api/1.0/weighings';

    /** @test */
    public function shouldUpdateLoadWeighing()
    {
        $this->seed(TruckScalePackageSeeder::class);
        $record = factory(Weighing::class)->create([
            'weighing_type' => WeighingType::Load,
            'tare_weight' => 85,
            'gross_weight' => 0
        ]);

        $payload = [
            'weighing_type' => WeighingType::Load,
            'gross_weight' => 100
        ];

        $this->actingAsAdmin(factory(User::class)->create())
            ->json($this->method, "{$this->path}/{$record->id}", $payload)
            ->assertOk();

        // only the gross weight should change
        $this->assertDatabaseHas('weighings', [
            'id' => $record->id,
            'weighing_type' => WeighingType::Load,
            'tare_weight' => 85,
            'gross_weight' => 100,
        ]);
    }

    /** @test */
    public function shouldUpdateUnloadWeighing()
    {
        $this->seed(TruckScalePackageSeeder::class);
        $record = factory(Weighing::class)->create([
            'weighing_type' => WeighingType::Unload,
            'tare_weight' => 0,
            'gross_weight' => 120,
        ]);

        $payload = [
            'weighing_type' => WeighingType::Unload,
            'tare_weight' => 15
        ];

        $this->actingAsAdmin(factory(User::class)->create())
            ->json($this->method, "{$this->path}/{$record->id}", $payload)
            ->assertOk();

        // only the tare weight should change
        $this->assertDatabaseHas('weighings', [
            'id' => $record->id,
            'weighing_type' => WeighingType::Unload,
            'tare_weight' => 15,
            'gross_weight' => 120,
        ]);
    }
}
