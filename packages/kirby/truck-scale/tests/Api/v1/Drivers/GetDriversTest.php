<?php

namespace Kirby\TruckScale\Tests\Api\V1\Drivers;

use Kirby\TruckScale\Models\Weighing;
use Kirby\Users\Models\User;
use Tests\TestCase;

class GetDriversTest extends TestCase
{
    private $method = 'GET';
    private $path = 'api/1.0/drivers';

    /** @test */
    public function shouldSearchDriversByPartialID()
    {
        factory(Weighing::class)->create(['driver_dni_number' => '1234', 'driver_name' => 'John Doe']);
        factory(Weighing::class)->create(['driver_dni_number' => '5678', 'driver_name' => 'Jane Doe']);
        factory(Weighing::class)->create(['driver_dni_number' => '1234', 'driver_name' => 'John Doe']);
        factory(Weighing::class)->create(['driver_dni_number' => '9876', 'driver_name' => 'James Doe']);

        $this->actingAsAdmin()
            ->json($this->method, "{$this->path}?s=34")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 1234)
            ->assertJsonPath('data.0.name', 'John Doe');
    }
}
