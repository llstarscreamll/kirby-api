<?php

namespace Kirby\Novelties\Tests\api\Novelties;

use Kirby\Novelties\Novelties;
use NoveltiesPackageSeed;

/**
 * Class GetSettingsTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class GetSettingsTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/novelties/settings';

    public function setUp(): void
    {
        parent::setUp();

        $this->seed(NoveltiesPackageSeed::class);
        $this->actingAsAdmin($this->user = factory(\Kirby\Users\Models\User::class)->create());
    }

    /**
     * @test
     */
    public function getShouldReturnOk()
    {
        $this->mock(Novelties::class)
            ->shouldReceive('settings')
            ->andReturn(collect(['foo' => 'bar']));

        $this->json('GET', $this->endpoint)
            ->assertOk()
            ->assertJsonHasPath('data.foo');
    }

    /**
     * @test
     */
    public function shouldReturnForbiddenWhenUserDoesntHaveRequiredPermissions()
    {
        $endpoint = str_replace('{id}', 1, $this->endpoint);

        $this->actingAsGuest()->json('GET', $endpoint)->assertForbidden();
    }
}
