<?php

namespace Kirby\TruckScale\Tests\Api\V1\Drivers;

use Tests\TestCase;
use TruckScalePackageSeeder;

class UpdateSettingsTest extends TestCase
{
    private $method = 'PUT';
    private $path = 'api/1.0/truck-scale-settings/toggle-require-weighing-machine-lecture';

    /** @test */
    public function shouldReturnAllModuleSettings()
    {
        $this->seed(TruckScalePackageSeeder::class);

        $this->assertDatabaseHas('settings', [
            'key' => 'truck-scale.require-weighing-machine-lecture',
            'value' => 'ON'
        ]);

        $this->actingAsAdmin()
            ->json($this->method, $this->path)
            ->assertOk()
            ->assertJsonPath('data', 'ok');

        $this->assertDatabaseHas('settings', [
            'key' => 'truck-scale.require-weighing-machine-lecture',
            'value' => 'OFF'
        ]);
    }
}
