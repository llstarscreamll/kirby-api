<?php

namespace Kirby\Novelties\Tests;

use NoveltiesPackageSeed;
use Kirby\Novelties\Models\Novelty;

/**
 * Class GetNoveltyTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class GetNoveltyTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/novelties/{id}';

    public function setUp(): void
    {
        parent::setUp();
        $this->seed(NoveltiesPackageSeed::class);
        $this->actingAsAdmin($this->user = factory(\Kirby\Users\Models\User::class)->create());
    }

    /**
     * @test
     */
    public function getNoveltySuccessfully()
    {
        $novelty = factory(Novelty::class)->create();
        $endpoint = str_replace('{id}', $novelty->id, $this->endpoint);

        $this->json('GET', $endpoint)
            ->assertOk()
            ->assertJsonHasPath('data.id')
            ->assertJsonHasPath('data.time_clock_log_id')
            ->assertJsonHasPath('data.employee_id')
            ->assertJsonHasPath('data.novelty_type_id')
            ->assertJsonHasPath('data.novelty_type')
            ->assertJsonHasPath('data.employee')
            ->assertJsonHasPath('data.time_clock_log')
            ->assertJsonHasPath('data.approvals');
    }

    /**
     * @test
     */
    public function shouldReturnForbidenWhenUserDoesntHaveRequiredPermissions()
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();
        $novelty = factory(Novelty::class)->create();
        $endpoint = str_replace('{id}', $novelty->id, $this->endpoint);

        $this->json('GET', $endpoint)
            ->assertForbidden();
    }
}
