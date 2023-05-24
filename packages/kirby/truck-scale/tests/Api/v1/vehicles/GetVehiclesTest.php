<?php

namespace Kirby\TruckScale\Tests\Api\V1\Vehicles;

use Kirby\TruckScale\Enums\VehicleType;
use Kirby\TruckScale\Models\Weighing;
use Kirby\Users\Models\User;
use Tests\TestCase;

class GetVehiclesTest extends TestCase
{
    private $method = 'GET';
    private $path = 'api/1.0/vehicles';

    /** @test */
    public function shouldReturn200WithPaginatedVehiclesSortedByNameAsc()
    {
        factory(Weighing::class)->create(['vehicle_plate' => 'AAA111', 'vehicle_type' => VehicleType::One()]);
        factory(Weighing::class)->create(['vehicle_plate' => 'BBB222', 'vehicle_type' => VehicleType::Two()]);
        factory(Weighing::class)->create(['vehicle_plate' => 'AAA111', 'vehicle_type' => VehicleType::One()]);
        factory(Weighing::class)->create(['vehicle_plate' => 'CCC333', 'vehicle_type' => VehicleType::One()]);

        $this->actingAs(factory(User::class)->create())
            ->json($this->method, $this->path)
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.plate', 'AAA111')
            ->assertJsonPath('data.1.plate', 'BBB222')
            ->assertJsonPath('data.2.plate', 'CCC333');
    }

    /** @test */
    public function shouldFilterVehiclesByPartPlateTerm()
    {
        factory(Weighing::class)->create(['vehicle_plate' => 'AAA111', 'vehicle_type' => VehicleType::One()]);
        factory(Weighing::class)->create(['vehicle_plate' => 'BBB222', 'vehicle_type' => VehicleType::Two()]);
        factory(Weighing::class)->create(['vehicle_plate' => 'AAA111', 'vehicle_type' => VehicleType::One()]);
        factory(Weighing::class)->create(['vehicle_plate' => 'CCC333', 'vehicle_type' => VehicleType::One()]);
        factory(Weighing::class)->create(['vehicle_plate' => 'AAA222', 'vehicle_type' => VehicleType::One()]);

        $this->actingAs(factory(User::class)->create())
            ->json($this->method, "{$this->path}?s=AAA")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.plate', 'AAA111')
            ->assertJsonPath('data.1.plate', 'AAA222');
    }

    /** @test */
    public function shouldReturnVehiclesWithDriversInfo()
    {
        factory(Weighing::class)->create(['vehicle_plate' => 'AAA111', 'vehicle_type' => VehicleType::One(), 'driver_dni_number' => '1234', 'driver_name' => 'John']);
        factory(Weighing::class)->create(['vehicle_plate' => 'BBB222', 'vehicle_type' => VehicleType::Two()]);
        factory(Weighing::class)->create(['vehicle_plate' => 'AAA111', 'vehicle_type' => VehicleType::One(), 'driver_dni_number' => '5678', 'driver_name' => 'Jane']);
        factory(Weighing::class)->create(['vehicle_plate' => 'CCC333', 'vehicle_type' => VehicleType::One()]);
        factory(Weighing::class)->create(['vehicle_plate' => 'AAA222', 'vehicle_type' => VehicleType::One()]);

        $this->actingAs(factory(User::class)->create())
            ->json($this->method, "{$this->path}?s=AAA")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.plate', 'AAA111')
            ->assertJsonPath('data.0.drivers.0.id', '5678')
            ->assertJsonPath('data.0.drivers.0.name', 'Jane')
            ->assertJsonPath('data.0.drivers.1.id', '1234')
            ->assertJsonPath('data.0.drivers.1.name', 'John');
    }
}
