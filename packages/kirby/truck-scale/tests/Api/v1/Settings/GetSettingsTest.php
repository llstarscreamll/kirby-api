<?php

namespace Kirby\TruckScale\Tests\Api\V1\Drivers;

use Tests\TestCase;
use TruckScalePackageSeeder;

class GetSettingsTest extends TestCase
{
    private $method = 'GET';
    private $path = 'api/1.0/truck-scale-settings';

    /** @test */
    public function shouldReturnAllModuleSettings()
    {
        $this->seed(TruckScalePackageSeeder::class);

        $this->actingAsAdmin()
            ->json($this->method, "{$this->path}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.key', 'truck-scale.require-weighing-machine-lecture')
            ->assertJsonPath('data.0.value', 'ON');
    }
}
