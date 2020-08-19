<?php

namespace Kirby\Novelties\Tests;

use Kirby\Novelties\Models\NoveltyType;
use NoveltiesPackageSeed;

/**
 * Class GetNoveltyTypeTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class GetNoveltyTypeTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/novelty-types/{id}';

    public function setUp(): void
    {
        parent::setUp();
        $this->seed(NoveltiesPackageSeed::class);
        $this->actingAsAdmin($this->user = factory(\Kirby\Users\Models\User::class)->create());

    }

    /**
     * @test
     */
    public function getSuccessfully()
    {
        $expectedNoveltyType = factory(NoveltyType::class)->create();

        $endpoint = str_replace('{id}', $expectedNoveltyType->id, $this->endpoint);
        $this->json('GET', $endpoint)
            ->assertOk()
            ->assertJsonPath('data.id', $expectedNoveltyType->id);
    }

    /**
     * @test
     */
    public function shouldReturnForbidenWhenUserDoesntHaveRequiredPermissions()
    {
        $this->actingAsGuest()->json('GET', $this->endpoint)->assertForbidden();
    }
}
