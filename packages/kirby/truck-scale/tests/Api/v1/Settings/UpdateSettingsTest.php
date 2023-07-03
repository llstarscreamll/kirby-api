<?php

namespace Kirby\TruckScale\Tests\Api\V1\Drivers;

use Tests\TestCase;
use TruckScalePackageSeeder;

class UpdateSettingsTest extends TestCase
{
    private $method = 'PUT';
    private $path = 'api/1.0/truck-scale-settings/toggle-require-weighing-machine-lecture';

    /** @test */
    public function shouldToggleTheValueOfRequireWeighingMachineLectureFlag()
    {
        $this->seed(TruckScalePackageSeeder::class);

        $this->assertDatabaseHas('settings', [
            'key' => 'truck-scale.require-weighing-machine-lecture',
            'value' => 'ON',
        ]);

        $this->actingAsAdmin()
            ->json($this->method, $this->path)
            ->assertOk()
            ->assertJsonPath('data', 'ok');

        $this->assertDatabaseHas('settings', [
            'key' => 'truck-scale.require-weighing-machine-lecture',
            'value' => 'OFF',
        ]);
    }

    /** @test */
    public function shouldReturnForbiddenWhenUserDoesNotHaveEnoughPermissions()
    {
        $this->seed(TruckScalePackageSeeder::class);

        $this->assertDatabaseHas('settings', [
            'key' => 'truck-scale.require-weighing-machine-lecture',
            'value' => 'ON',
        ]);

        $this->actingAsGuest()
            ->json($this->method, $this->path)
            ->assertForbidden();

        // config value should not be changed
        $this->assertDatabaseHas('settings', [
            'key' => 'truck-scale.require-weighing-machine-lecture',
            'value' => 'ON',
        ]);
    }
}
