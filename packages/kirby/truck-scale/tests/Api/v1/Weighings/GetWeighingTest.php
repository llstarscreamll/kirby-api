<?php

namespace Kirby\TruckScale\Tests\Api\V1\Weighings;

use Kirby\TruckScale\Models\Weighing;
use Kirby\Users\Models\User;
use Tests\TestCase;
use TruckScalePackageSeeder;

class GetWeighingTest extends TestCase
{
    private $method = 'GET';
    private $path = 'api/1.0/weighings';

    /** @test */
    public function shouldReturnOkWithDataWhenIdExist()
    {
        $this->seed(TruckScalePackageSeeder::class);
        $record = factory(Weighing::class)->create();

        $this->actingAsAdmin(factory(User::class)->create())
            ->json($this->method, "{$this->path}/{$record->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $record->id)
            ->assertJsonPath('data.cancel_comment', $record->cancel_comment)
            ->assertJsonPath('data.created_by.first_name', $record->createdBy->first_name)
            ->assertJsonPath('data.created_by.last_name', $record->createdBy->last_name)
            ->assertJsonPath('data.updated_by.first_name', $record->updatedBy->first_name)
            ->assertJsonPath('data.updated_by.last_name', $record->updatedBy->last_name);
    }
}
