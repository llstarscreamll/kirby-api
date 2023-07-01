<?php

namespace Kirby\TruckScale\Tests\Api\V1\Drivers;

use Kirby\TruckScale\Models\Weighing;
use Tests\TestCase;

class GetCommoditiesTest extends TestCase
{
    private $method = 'GET';
    private $path = 'api/1.0/commodities';

    /** @test */
    public function shouldSearchCommoditiesByName()
    {
        factory(Weighing::class)->create(['commodity' => 'Lignite coal']);
        factory(Weighing::class)->create(['commodity' => 'Foo']);
        factory(Weighing::class)->create(['commodity' => 'Anthracite Coal']);
        factory(Weighing::class)->create(['commodity' => 'Bar']);

        $this->actingAsAdmin()
            ->json($this->method, "{$this->path}?s=coal")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Anthracite Coal')
            ->assertJsonPath('data.1.name', 'Lignite coal');
    }
}
