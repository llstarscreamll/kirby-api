<?php

namespace Kirby\TruckScale\Tests\Api\V1\Weighings;

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
            'status' => WeighingStatus::InProgress,
            'tare_weight' => 85,
            'gross_weight' => 0,
        ]);

        $payload = [
            'weighing_type' => WeighingType::Load,
            'gross_weight' => 100,
            'weighing_description' => 'test description',
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
            'weighing_description' => 'test description',
        ]);
    }

    /** @test */
    public function shouldSetFinishedStatusWhenLoadWeighingIsUpdated()
    {
        $this->seed(TruckScalePackageSeeder::class);
        $record = factory(Weighing::class)->create([
            'weighing_type' => WeighingType::Load,
            'status' => WeighingStatus::InProgress,
            'tare_weight' => 85,
            'gross_weight' => 0,
        ]);

        $payload = [
            'weighing_type' => WeighingType::Load,
            'gross_weight' => 100,
        ];

        $this->actingAsAdmin(factory(User::class)->create())
            ->json($this->method, "{$this->path}/{$record->id}", $payload)
            ->assertOk();

        $this->assertDatabaseHas('weighings', [
            'id' => $record->id,
            'status' => WeighingStatus::Finished,
        ]);
    }

    /** @test */
    public function shouldUpdateUnloadWeighing()
    {
        $this->seed(TruckScalePackageSeeder::class);
        $record = factory(Weighing::class)->create([
            'weighing_type' => WeighingType::Unload,
            'status' => WeighingStatus::InProgress,
            'tare_weight' => 0,
            'gross_weight' => 120,
        ]);

        $payload = [
            'weighing_type' => WeighingType::Unload,
            'tare_weight' => 15,
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

    /** @test */
    public function shouldSetFinishedStatusWhenUnloadWeighingIsUpdated()
    {
        $this->seed(TruckScalePackageSeeder::class);
        $record = factory(Weighing::class)->create([
            'weighing_type' => WeighingType::Unload,
            'status' => WeighingStatus::InProgress,
            'tare_weight' => 0,
            'gross_weight' => 120,
        ]);

        $payload = [
            'weighing_type' => WeighingType::Unload,
            'tare_weight' => 15,
        ];

        $this->actingAsAdmin(factory(User::class)->create())
            ->json($this->method, "{$this->path}/{$record->id}", $payload)
            ->assertOk();

        $this->assertDatabaseHas('weighings', [
            'id' => $record->id,
            'status' => WeighingStatus::Finished,
        ]);
    }

    /** @test */
    public function shouldSaveTheUserIdWhoUpdatedTheRecord()
    {
        $this->seed(TruckScalePackageSeeder::class);
        $record = factory(Weighing::class)->create([
            'weighing_type' => WeighingType::Load,
            'status' => WeighingStatus::InProgress,
            'tare_weight' => 85,
            'gross_weight' => 0,
        ]);

        $payload = [
            'weighing_type' => WeighingType::Load,
            'gross_weight' => 100,
        ];

        $this->actingAsAdmin($user = factory(User::class)->create())
            ->json($this->method, "{$this->path}/{$record->id}", $payload)
            ->assertOk();

        $this->assertDatabaseHas('weighings', [
            'id' => $record->id,
            'updated_by_id' => $user->id,
        ]);
    }

    /** @test */
    public function shouldReturnErrorWhenWeighingTypeIsWeighing()
    {
        $this->seed(TruckScalePackageSeeder::class);
        $record = factory(Weighing::class)->create([
            'weighing_type' => WeighingType::Weighing,
            'tare_weight' => 0,
            'gross_weight' => 150,
        ]);

        $payload = [
            'weighing_type' => WeighingType::Weighing,
            'tare_weight' => 15,
        ];

        $this->actingAsAdmin(factory(User::class)->create())
            ->json($this->method, "{$this->path}/{$record->id}", $payload)
            ->assertStatus(422)
            ->assertJsonPath('errors.weighing_type.0', 'Solo se permite actualizaciones a registros de tipo cargue y descargue');
    }

    /** @test */
    public function shouldReturnErrorWhenAnotherWeighingTypeIsGivenAndStatusIsAlreadyFinished()
    {
        $this->seed(TruckScalePackageSeeder::class);
        $record = factory(Weighing::class)->create([
            'weighing_type' => WeighingType::Weighing,
            'status' => WeighingStatus::Finished,
            'tare_weight' => 0,
            'gross_weight' => 150,
        ]);

        // some one trying to hacking the system to update finished data
        $payload = [
            'weighing_type' => WeighingType::Load, // different type trying to skip type validation
            'gross_weight' => 250, // increased gross weight
        ];

        $this->actingAsAdmin(factory(User::class)->create())
            ->json($this->method, "{$this->path}/{$record->id}", $payload)
            ->assertStatus(422)
            ->assertJsonPath('errors.status.0', 'No se permite actualizaciones a registros finalizados');
    }

    /** @test */
    public function shouldReturnErrorUpdatingLoadWeighingWhenStatusIsAlreadyFinished()
    {
        $this->seed(TruckScalePackageSeeder::class);
        $record = factory(Weighing::class)->create([
            'weighing_type' => WeighingType::Load,
            'status' => WeighingStatus::Finished,
            'tare_weight' => 85,
            'gross_weight' => 100,
        ]);

        $payload = [
            'weighing_type' => WeighingType::Load,
            'gross_weight' => 250,
        ];

        $this->actingAsAdmin(factory(User::class)->create())
            ->json($this->method, "{$this->path}/{$record->id}", $payload)
            ->assertStatus(422)
            ->assertJsonPath('errors.status.0', 'No se permite actualizaciones a registros finalizados');
    }

    /** @test */
    public function shouldReturnErrorUpdatingUnloadWeighingWhenStatusIsAlreadyFinished()
    {
        $this->seed(TruckScalePackageSeeder::class);
        $record = factory(Weighing::class)->create([
            'weighing_type' => WeighingType::Unload,
            'status' => WeighingStatus::Finished,
            'tare_weight' => 0,
            'gross_weight' => 100,
        ]);

        $payload = [
            'weighing_type' => WeighingType::Unload,
            'tare_weight' => 25,
        ];

        $this->actingAsAdmin(factory(User::class)->create())
            ->json($this->method, "{$this->path}/{$record->id}", $payload)
            ->assertStatus(422)
            ->assertJsonPath('errors.status.0', 'No se permite actualizaciones a registros finalizados');
    }

    /** @test */
    public function shouldReturnErrorUpdatingLoadWeighingWhenTareIsGreaterThanGrossWeight()
    {
        $this->seed(TruckScalePackageSeeder::class);
        $record = factory(Weighing::class)->create([
            'weighing_type' => WeighingType::Load,
            'status' => WeighingStatus::InProgress,
            'tare_weight' => 50,
            'gross_weight' => 0,
        ]);

        $payload = [
            'weighing_type' => WeighingType::Load,
            'gross_weight' => 49,
        ];

        $this->actingAsAdmin(factory(User::class)->create())
            ->json($this->method, "{$this->path}/{$record->id}", $payload)
            ->assertStatus(422)
            ->assertJsonPath('errors.gross_weight.0', 'Peso tara no puede ser mayor que peso bruto.');
    }

    /** @test */
    public function shouldReturnErrorUpdatingUnloadWeighingWhenTareIsGreaterThanGrossWeight()
    {
        $this->seed(TruckScalePackageSeeder::class);
        $record = factory(Weighing::class)->create([
            'weighing_type' => WeighingType::Unload,
            'status' => WeighingStatus::InProgress,
            'tare_weight' => 0,
            'gross_weight' => 100,
        ]);

        $payload = [
            'weighing_type' => WeighingType::Unload,
            'tare_weight' => 101,
        ];

        $this->actingAsAdmin(factory(User::class)->create())
            ->json($this->method, "{$this->path}/{$record->id}", $payload)
            ->assertStatus(422)
            ->assertJsonPath('errors.tare_weight.0', 'Peso tara no puede ser mayor que peso bruto.');
    }

    public function wrongInputDataProvider(): array
    {
        return [
            [
                'case' => 'tare weight should be greater than 0',
                ['weighing_type' => WeighingType::Unload, 'status' => WeighingStatus::InProgress, 'tare_weight' => 0, 'gross_weight' => 100],
                ['weighing_type' => WeighingType::Unload, 'tare_weight' => 0],
                ['tare_weight' => 'El tamaño de peso tara debe ser de al menos 1.'],
            ],
            [
                'case' => 'gross weight should be greater than 0',
                ['weighing_type' => WeighingType::Load, 'status' => WeighingStatus::InProgress, 'tare_weight' => 80, 'gross_weight' => 0],
                ['weighing_type' => WeighingType::Load, 'gross_weight' => 0],
                ['gross_weight' => 'El tamaño de peso bruto debe ser de al menos 1.'],
            ],
            [
                'case' => 'gross weight should not be equal to tare weight',
                ['weighing_type' => WeighingType::Load, 'status' => WeighingStatus::InProgress, 'tare_weight' => 80, 'gross_weight' => 0],
                ['weighing_type' => WeighingType::Load, 'gross_weight' => 80],
                ['gross_weight' => 'Peso tara y peso bruto no pueden ser iguales.'],
            ],
        ];
    }

    /**
     * @dataProvider wrongInputDataProvider
     *
     * @test
     */
    public function shouldReturn422WhenInputIsNotValid($_, array $weighing, array $payload, array $errors)
    {
        $this->seed(TruckScalePackageSeeder::class);
        $record = factory(Weighing::class)->create($weighing);

        $this->actingAsAdmin()
            ->json($this->method, "{$this->path}/{$record->id}", $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors($errors);
    }
}
