<?php

namespace Kirby\TruckScale\Tests\Api\V1\Weighings;

use Kirby\TruckScale\Enums\VehicleType;
use Kirby\TruckScale\Enums\WeighingType;
use Kirby\TruckScale\Models\Weighing;
use Kirby\Users\Models\User;
use Tests\TestCase;
use TruckScalePackageSeeder;

class CreateWeighingTest extends TestCase
{
    private $method = 'POST';
    private $path = 'api/1.0/weighings';

    /** @test */
    public function shouldCreateWeighingWithLoadTypeWhenInputIsValid()
    {
        $this->seed(TruckScalePackageSeeder::class);
        $payload = [
            'weighing_type' => WeighingType::Load,
            'vehicle_plate' => 'ABC123',
            'vehicle_type' => VehicleType::One,
            'driver_dni_number' => 1234,
            'driver_name' => 'John Doe',
            'tare_weight' => 120.05,
            'gross_weight' => null,
            'weighing_description' => null,
        ];

        $this->actingAsAdmin()
            ->json($this->method, $this->path, $payload)
            ->assertCreated();

        $this->assertDatabaseHas('weighings', ['gross_weight' => 0, 'weighing_description' => ''] + $payload);
    }

    /** @test */
    public function shouldCreateWeighingWithUnloadTypeWhenInputIsValid()
    {
        $this->seed(TruckScalePackageSeeder::class);
        $payload = [
            'weighing_type' => WeighingType::Unload,
            'vehicle_plate' => 'ABC123',
            'vehicle_type' => VehicleType::One,
            'driver_dni_number' => 1234,
            'driver_name' => 'John Doe',
            'tare_weight' => null,
            'gross_weight' => 130.05,
            'weighing_description' => 'Test description',
        ];

        $this->actingAsAdmin()
            ->json($this->method, $this->path, $payload)
            ->assertCreated();

        $this->assertDatabaseHas('weighings', ['tare_weight' => 0] + $payload);
    }

    /** @test */
    public function shouldCreateWeighingWithWeighingTypeWhenInputIsValid()
    {
        $this->seed(TruckScalePackageSeeder::class);
        $payload = [
            'weighing_type' => WeighingType::Weighing,
            'vehicle_plate' => 'ABC123',
            'vehicle_type' => VehicleType::One,
            'driver_dni_number' => 1234,
            'driver_name' => 'John Doe',
            'tare_weight' => null,
            'gross_weight' => 210.05,
            'weighing_description' => 'Some description',
        ];

        $this->actingAsAdmin()
            ->json($this->method, $this->path, $payload)
            ->assertCreated();

        $this->assertDatabaseHas('weighings', ['tare_weight' => 0] + $payload);
    }
}
