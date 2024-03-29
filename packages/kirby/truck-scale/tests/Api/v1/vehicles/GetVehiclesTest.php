<?php

namespace Kirby\TruckScale\Tests\Api\V1\Vehicles;

use Kirby\TruckScale\Enums\VehicleType;
use Kirby\TruckScale\Models\Weighing;
use Kirby\Users\Models\User;
use Tests\TestCase;
use TruckScalePackageSeeder;

class GetVehiclesTest extends TestCase
{
    private $method = 'GET';
    private $path = 'api/1.0/vehicles';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TruckScalePackageSeeder::class);
    }

    /** @test */
    public function shouldReturn200WithPaginatedVehiclesSortedByNameAsc()
    {
        factory(Weighing::class)->create(['vehicle_plate' => 'AAA111', 'vehicle_type' => VehicleType::One()]);
        factory(Weighing::class)->create(['vehicle_plate' => 'BBB222', 'vehicle_type' => VehicleType::Two()]);
        factory(Weighing::class)->create(['vehicle_plate' => 'AAA111', 'vehicle_type' => VehicleType::One()]);
        factory(Weighing::class)->create(['vehicle_plate' => 'CCC333', 'vehicle_type' => VehicleType::One()]);

        $this->actingAsAdmin(factory(User::class)->create())
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

        $this->actingAsAdmin(factory(User::class)->create())
            ->json($this->method, "{$this->path}?s=AAA")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.plate', 'AAA111')
            ->assertJsonPath('data.1.plate', 'AAA222');
    }

    /** @test */
    public function shouldReturnVehiclesWithDriversInfo()
    {
        // too old row, it should not returned on results
        factory(Weighing::class)->create(['vehicle_plate' => 'AAA111', 'vehicle_type' => VehicleType::One(), 'driver_dni_number' => '9012', 'driver_name' => 'James', 'created_at' => now()->subMonths(7)]);
        // fresh data
        factory(Weighing::class)->create(['vehicle_plate' => 'AAA111', 'vehicle_type' => VehicleType::One(), 'driver_dni_number' => '1234', 'driver_name' => 'John']);
        factory(Weighing::class)->create(['vehicle_plate' => 'BBB222', 'vehicle_type' => VehicleType::Two()]);
        factory(Weighing::class)->create(['vehicle_plate' => 'AAA111', 'vehicle_type' => VehicleType::One(), 'driver_dni_number' => '5678', 'driver_name' => 'Jane']);
        factory(Weighing::class)->create(['vehicle_plate' => 'CCC333', 'vehicle_type' => VehicleType::One()]);
        factory(Weighing::class)->create(['vehicle_plate' => 'AAA222', 'vehicle_type' => VehicleType::One()]);
        // repeated driver row, should appear only once on results
        factory(Weighing::class)->create(['vehicle_plate' => 'AAA111', 'vehicle_type' => VehicleType::One(), 'driver_dni_number' => '5678', 'driver_name' => 'Jane']);

        $this->actingAsAdmin(factory(User::class)->create())
            ->json($this->method, "{$this->path}?s=AAA111")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonCount(2, 'data.0.drivers')
            ->assertJsonPath('data.0.plate', 'AAA111')
            ->assertJsonPath('data.0.drivers.0.id', '5678')
            ->assertJsonPath('data.0.drivers.0.name', 'Jane')
            ->assertJsonPath('data.0.drivers.1.id', '1234')
            ->assertJsonPath('data.0.drivers.1.name', 'John');
    }

    /** @test */
    public function shouldReturnVehiclesWithClientsInfo()
    {
        // too old row, it should not returned on results
        factory(Weighing::class)->create(['vehicle_plate' => 'AAA111', 'vehicle_type' => VehicleType::One(), 'client' => 'FFF', 'created_at' => now()->subMonths(7)]);
        // fresh data
        factory(Weighing::class)->create(['vehicle_plate' => 'AAA111', 'vehicle_type' => VehicleType::One(), 'client' => 'GGG']);
        factory(Weighing::class)->create(['vehicle_plate' => 'BBB222', 'vehicle_type' => VehicleType::Two()]);
        factory(Weighing::class)->create(['vehicle_plate' => 'AAA111', 'vehicle_type' => VehicleType::One(), 'client' => 'HHH']);
        factory(Weighing::class)->create(['vehicle_plate' => 'CCC333', 'vehicle_type' => VehicleType::One()]);
        factory(Weighing::class)->create(['vehicle_plate' => 'AAA222', 'vehicle_type' => VehicleType::One()]);
        // repeated driver row, should appear only once on results
        factory(Weighing::class)->create(['vehicle_plate' => 'AAA111', 'vehicle_type' => VehicleType::One(), 'client' => 'GGG']);

        $this->actingAsAdmin(factory(User::class)->create())
            ->json($this->method, "{$this->path}?s=AAA111")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonCount(2, 'data.0.clients')
            ->assertJsonPath('data.0.plate', 'AAA111')
            ->assertJsonPath('data.0.clients.0.name', 'GGG')
            ->assertJsonPath('data.0.clients.1.name', 'HHH');
    }

    /** @test */
    public function shouldReturnVehiclesWithCommoditiesInfo()
    {
        // too old row, it should not returned on results
        factory(Weighing::class)->create(['vehicle_plate' => 'AAA111', 'vehicle_type' => VehicleType::One(), 'commodity' => 'FFF', 'created_at' => now()->subMonths(7)]);
        // fresh data
        factory(Weighing::class)->create(['vehicle_plate' => 'AAA111', 'vehicle_type' => VehicleType::One(), 'commodity' => 'GGG']);
        factory(Weighing::class)->create(['vehicle_plate' => 'BBB222', 'vehicle_type' => VehicleType::Two()]);
        factory(Weighing::class)->create(['vehicle_plate' => 'AAA111', 'vehicle_type' => VehicleType::One(), 'commodity' => 'HHH']);
        factory(Weighing::class)->create(['vehicle_plate' => 'CCC333', 'vehicle_type' => VehicleType::One()]);
        factory(Weighing::class)->create(['vehicle_plate' => 'AAA222', 'vehicle_type' => VehicleType::One()]);
        // repeated driver row, should appear only once on results
        factory(Weighing::class)->create(['vehicle_plate' => 'AAA111', 'vehicle_type' => VehicleType::One(), 'commodity' => 'GGG']);

        $this->actingAsAdmin(factory(User::class)->create())
            ->json($this->method, "{$this->path}?s=AAA111")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonCount(2, 'data.0.commodities')
            ->assertJsonPath('data.0.plate', 'AAA111')
            ->assertJsonPath('data.0.commodities.0.name', 'GGG')
            ->assertJsonPath('data.0.commodities.1.name', 'HHH');
    }
}
