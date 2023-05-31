<?php

namespace Kirby\TruckScale\Tests\Api\V1\Weighings;

use Kirby\TruckScale\Enums\VehicleType;
use Kirby\TruckScale\Models\Weighing;
use Tests\TestCase;

/**
 * @internal
 */
class SearchWeighingsTest extends TestCase
{
    private $method = 'GET';
    private $path = 'api/1.0/weighings';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\TruckScalePackageSeeder::class);
    }

    /** @test */
    public function shouldReturn200WithNewestWeighings()
    {
        $weighings = factory(Weighing::class, 11)->create()->reverse();

        $this
            ->actingAsAdmin()
            ->json($this->method, $this->path)
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('data.0.id', $weighings->first()->id);
    }

    /** @test */
    public function shouldSearchByID()
    {
        factory(Weighing::class, 5)->create();
        $expectedWeighing = factory(Weighing::class)->create();

        $this
            ->actingAsAdmin()
            ->json($this->method, "{$this->path}?filter[id]={$expectedWeighing->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $expectedWeighing->id);
    }

    /** @test */
    public function shouldSearchByVehiclePlate()
    {
        factory(Weighing::class, 5)->create();
        $expectedWeighing = factory(Weighing::class)->create(['vehicle_plate' => 'AAA001']);

        $this
            ->actingAsAdmin()
            ->json($this->method, "{$this->path}?filter[vehicle_plate]={$expectedWeighing->vehicle_plate}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $expectedWeighing->id);
    }

    /** @test */
    public function shouldSearchByVehicleType()
    {
        factory(Weighing::class, 5)->create(['vehicle_type' => VehicleType::One]);
        $expectedWeighing = factory(Weighing::class)->create(['vehicle_type' => VehicleType::Two]);

        $this
            ->actingAsAdmin()
            ->json($this->method, "{$this->path}?filter[vehicle_type]={$expectedWeighing->vehicle_type}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $expectedWeighing->id);
    }
}
