<?php

namespace Kirby\Novelties\Tests\api\Novelties;

use Kirby\Novelties\Models\Novelty;
use NoveltiesPackageSeed;

/**
 * Class DeleteNoveltyApprovalTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class DeleteNoveltyApprovalTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/novelties/{novelty-id}/approvals/{approval-id}';

    /**
     * @var \Kirby\Users\Models\User
     */
    private $user;

    /**
     * @var \Illuminate\Support\Collection
     */
    private $novelties;

    /**
     * @var string
     */
    private $approvalId;

    public function setUp(): void
    {
        parent::setUp();

        $this->seed(NoveltiesPackageSeed::class);
        $this->actingAsAdmin($this->user = factory(\Kirby\Users\Models\User::class)->create());
        $this->novelties = factory(Novelty::class, 2)->create();
        $this->approvalId = 1;
        $this->haveRecord('novelty_approvals', [
            'user_id' => $this->user->id,
            'novelty_id' => $this->novelties->first()->id,
        ]);
    }

    /**
     * @test
     */
    public function shouldDeleteApprovalSuccessfully()
    {
        $endpoint = str_replace(
            ['{novelty-id}', '{approval-id}'],
            [$this->novelties->first()->id, $this->approvalId],
            $this->endpoint
        );

        $this->json('DELETE', $endpoint)->assertOk();

        $this->assertDatabaseMissing('novelty_approvals', [
            'user_id' => $this->user->id,
            'novelty_id' => $this->novelties->first()->id,
        ]);
    }

    /**
     * @test
     */
    public function shouldReturnForbiddenWhenUserDoesntHaveRequiredPermissions()
    {
        $endpoint = str_replace(['{novelty-id}', '{approval-id}'], [1, 2], $this->endpoint);

        $this->actingAsGuest()
            ->json('DELETE', $endpoint)
            ->assertForbidden();

        $this->assertDatabaseHas('novelty_approvals', [
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
        $this->json('DELETE', $endpoint)->assertNotFound();
    }
}
