<?php

namespace Kirby\TruckScale\Tests\Api\V1\Weighings;

use Kirby\TruckScale\Enums\VehicleType;
use Kirby\TruckScale\Enums\WeighingStatus;
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

        $this->actingAsAdmin($user = factory(User::class)->create())
            ->json($this->method, $this->path, $payload)
            ->assertCreated();

        $this->assertDatabaseHas('weighings', [
            'gross_weight' => 0,
            'weighing_description' => '',
            'status' => WeighingStatus::InProgress,
            'created_by_id' => $user->id,
        ] + $payload);
    }

    /** @test */
    public function shouldCreateWeighingWithUnloadTypeWhenInputIsValid()
    {
        $this->seed(TruckScalePackageSeeder::class);
        $payload = [
            'weighing_type' => WeighingType::Unload,
            'vehicle_plate' => 'AB123',
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

        $this->assertDatabaseHas('weighings', ['tare_weight' => 0, 'status' => WeighingStatus::InProgress] + $payload);
    }

    /** @test */
    public function shouldCreateWeighingWithWeighingTypeWhenInputIsValid()
    {
        $this->seed(TruckScalePackageSeeder::class);
        $payload = [
            'weighing_type' => WeighingType::Weighing,
            'vehicle_plate' => 'A123',
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

        $this->assertDatabaseHas('weighings', ['tare_weight' => 0, 'status' => WeighingStatus::Finished] + $payload);
    }

    /** @test */
    public function shouldCleanAndFormatSomeFields()
    {
        $this->seed(TruckScalePackageSeeder::class);
        $payload = [
            'weighing_type' => WeighingType::Weighing,
            'vehicle_plate' => "\n\tabc12345\t\n\t",
            'vehicle_type' => VehicleType::One,
            'driver_dni_number' => 1234,
            'driver_name' => "\n \t\n\n\nJohn \t\t\n\n Doe\n ",
            'tare_weight' => null,
            'gross_weight' => 210.05,
            'weighing_description' => " \t\nSome \n\ndescription \t\n",
        ];

        $this->actingAsAdmin()
            ->json($this->method, $this->path, $payload)
            ->assertCreated();

        $weighing = Weighing::where('vehicle_plate', 'abc12345')->first();
        $this->assertEquals('JOHN DOE', $weighing->driver_name);
        $this->assertEquals('ABC12345', $weighing->vehicle_plate);
        $this->assertEquals("Some \ndescription", $weighing->weighing_description);
    }

    /**
     * @dataProvider wrongInputDataProvider
     *
     * @test
     */
    public function shouldReturn422WhenInputIsNotValid($_, $payload, $errors)
    {
        $this->seed(TruckScalePackageSeeder::class);

        $this->actingAsAdmin()
            ->json($this->method, $this->path, $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors($errors);
    }

    public function wrongInputDataProvider(): array
    {
        return [
            [
                'case' => 'vehicle plate should have 3 letters max',
                ['vehicle_plate' => 'ABCDEF4'],
                ['vehicle_plate' => 'El formato de placa de vehículo no es válido'],
            ],
            [
                'case' => 'vehicle plate should have 1 letters min',
                ['vehicle_plate' => '12345678'],
                ['vehicle_plate' => 'El formato de placa de vehículo no es válido'],
            ],
            [
                'case' => 'vehicle plate should have 3 letters min',
                ['vehicle_plate' => 'AAA87'],
                ['vehicle_plate' => 'El formato de placa de vehículo no es válido'],
            ],
            [
                'case' => 'vehicle plate should have 5 letters max',
                ['vehicle_plate' => 'AA777777'],
                ['vehicle_plate' => 'El formato de placa de vehículo no es válido'],
            ],
            [
                'case' => 'vehicle plate should start with a letter',
                ['vehicle_plate' => '123AAA'],
                ['vehicle_plate' => 'El formato de placa de vehículo no es válido'],
            ],
            [
                'case' => 'vehicle plate should finish with a number',
                ['vehicle_plate' => 'AA123A'],
                ['vehicle_plate' => 'El formato de placa de vehículo no es válido'],
            ],
            [
                'case' => 'vehicle plate should not have accented chars',
                ['vehicle_plate' => 'ÁÑÓ123'],
                ['vehicle_plate' => 'El formato de placa de vehículo no es válido'],
            ],
            [
                'case' => 'tare weight should be greater than 0',
                ['weighing_type' => 'load', 'tare_weight' => 0],
                ['tare_weight' => 'El tamaño de peso tara debe ser de al menos 1.'],
            ],
            [
                'case' => 'gross weight should be greater than 0',
                ['weighing_type' => 'unload', 'gross_weight' => 0],
                ['gross_weight' => 'El tamaño de peso bruto debe ser de al menos 1.'],
            ],
        ];
    }
}
