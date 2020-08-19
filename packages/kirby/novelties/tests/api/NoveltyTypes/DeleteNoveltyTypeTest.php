<?php

namespace Kirby\Novelties\Tests;

use Kirby\Novelties\Models\NoveltyType;
use NoveltiesPackageSeed;

/**
 * Class DeleteNoveltyTypeTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class DeleteNoveltyTypeTest extends \Tests\TestCase
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
        $noveltyTypeId = factory(NoveltyType::class)->create()->id;

        // novelty type should exists but isn't soft deleted
        $this->assertDatabaseHas('novelty_types', [
            'id' => $noveltyTypeId,
            'deleted_at' => null,
        ]);

        $endpoint = str_replace('{id}', $noveltyTypeId, $this->endpoint);
        $this->json('DELETE', $endpoint)
            ->assertOk()
            ->assertJsonPath('data', 'ok');

        // novelty type should be soft deleted
        $this->assertSoftDeleted('novelty_types', ['id' => $noveltyTypeId]);
    }

    /**
     * @test
     */
    public function shouldReturnForbidenWhenUserDoesntHaveRequiredPermissions()
    {
        $endpoint = str_replace('{id}', 1, $this->endpoint);

        $this->actingAsGuest()
            ->json('DELETE', $endpoint)
            ->assertForbidden();
    }
}
