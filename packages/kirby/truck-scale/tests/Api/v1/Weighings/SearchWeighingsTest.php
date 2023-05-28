<?php

namespace Kirby\TruckScale\Tests\Api\V1\Weighings;

use Kirby\TruckScale\Models\Weighing;
use Tests\TestCase;
use TruckScalePackageSeeder;

class SearchWeighingsTest extends TestCase
{
    private $method = 'GET';
    private $path = 'api/1.0/weighings';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TruckScalePackageSeeder::class);
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
}
