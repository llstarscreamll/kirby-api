<?php

namespace Kirby\TruckScale\Tests\Api\V1\Drivers;

use Kirby\TruckScale\Models\Weighing;
use Kirby\Users\Models\User;
use Tests\TestCase;

class GetClientsTest extends TestCase
{
    private $method = 'GET';
    private $path = 'api/1.0/clients';

    /** @test */
    public function shouldSearchClientsByName()
    {
        factory(Weighing::class)->create(['client' => 'Warner Bros Inc.']);
        factory(Weighing::class)->create(['client' => 'Foo']);
        factory(Weighing::class)->create(['client' => 'Acme Inc.']);
        factory(Weighing::class)->create(['client' => 'Bar']);

        $this->actingAsAdmin()
            ->json($this->method, "{$this->path}?s=inc")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Acme Inc.')
            ->assertJsonPath('data.1.name', 'Warner Bros Inc.');
    }
}
