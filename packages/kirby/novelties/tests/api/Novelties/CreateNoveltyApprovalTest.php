<?php

namespace Kirby\Novelties\Tests\api\Novelties;

use Kirby\Novelties\Models\Novelty;
use NoveltiesPackageSeed;

/**
 * Class CreateNoveltyApprovalTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateNoveltyApprovalTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/novelties/{novelty-id}/approvals';

    /**
     * @var \Kirby\Users\Models\User
     */
    private $user;

    /**
     * @var \Illuminate\Support\Collection
     */
    private $novelties;

    public function setUp(): void
    {
        parent::setUp();

        $this->seed(NoveltiesPackageSeed::class);
        $this->actingAsAdmin($this->user = factory(\Kirby\Users\Models\User::class)->create());
        $this->novelties = factory(Novelty::class, 2)->create();
    }

    /**
     * @test
     */
    public function shouldCreateApprovalSuccessfully()
    {
        $novelty = $this->novelties->first();
        $endpoint = str_replace('{novelty-id}', $novelty->id, $this->endpoint);
        $this->json('POST', $endpoint)
            ->assertCreated();
        $this->assertDatabaseHas('novelty_approvals', [
            'user_id' => $this->user->id,
            'novelty_id' => $novelty->id,
        ]);
    }

    /**
     * @test
     */
    public function shouldReturnForbiddenWhenUserDoesntHaveRequiredPermissions()
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();

        $endpoint = str_replace('{novelty-id}', $this->novelties->first()->id, $this->endpoint);
        $this->json('POST', $endpoint)
            ->assertForbidden();
        $this->assertDatabaseMissing('novelty_approvals', [
            'user_id' => $this->user->id,
            'novelty_id' => $this->novelties->first()->id,
        ]);
    }

    /**
     * @test
     */
    public function shouldReturnNotFoundIfNoveltyDoesntExists()
    {
        $endpoint = str_replace('{novelty-id}', 111, $this->endpoint);
        $this->json('POST', $endpoint)
            ->assertNotFound();
    }
}
