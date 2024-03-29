<?php

namespace Kirby\Novelties\Tests\api\NoveltyTypes;

use Kirby\Novelties\Models\NoveltyType;
use NoveltiesPackageSeed;

/**
 * Class SearchNoveltyTypesTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 *
 * @internal
 */
class SearchNoveltyTypesTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/novelty-types/';

    public function setUp(): void
    {
        parent::setUp();
        $this->seed(NoveltiesPackageSeed::class);
        $this->actingAsAdmin($this->user = factory(\Kirby\Users\Models\User::class)->create());
    }

    /**
     * @test
     */
    public function searchSuccessfully()
    {
        factory(NoveltyType::class, 5)->create();

        $this->json('GET', $this->endpoint)
            ->assertOk()
            ->assertJsonHasPath('data.0.id')
            ->assertJsonHasPath('data.1.id')
            ->assertJsonHasPath('data.2.id')
            ->assertJsonHasPath('data.3.id')
            ->assertJsonHasPath('data.4.id');
    }

    /**
     * @test
     */
    public function shouldReturnForbiddenWhenUserDoesntHaveRequiredPermissions()
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();

        $this->json('GET', $this->endpoint)
            ->assertForbidden();
    }
}
