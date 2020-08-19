<?php

namespace Kirby\Novelties\Tests\api\Novelties;

use NoveltiesPackageSeed;
use NoveltiesPermissionsSeeder;
use Kirby\Novelties\Models\Novelty;
use Illuminate\Support\Facades\Artisan;

/**
 * Class DeleteNoveltyTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class DeleteNoveltyTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/novelties/{id}';

    /**
     * @var \Kirby\Users\Models\User
     */
    private $user;

    /**
     * @var \Kirby\Novelties\Models\Novelty
     */
    private $novelty;

    
    public function setUp(): void
    {
        parent::setUp();

        $this->seed(NoveltiesPackageSeed::class);
        $this->novelty = factory(Novelty::class)->create();

        $this->actingAsAdmin($this->user = factory(\Kirby\Users\Models\User::class)->create());
    }

    /**
     * @test

     */
    public function shouldDeleteNoveltySuccessfully()
    {
        $endpoint = str_replace(
            '{id}',
            $this->novelty->id,
            $this->endpoint
        );
        $this->json('DELETE', $endpoint)
            ->assertOk();
        $this->assertDatabaseMissing('novelties', [
            'id' => $this->novelty->id,
            'deleted_at' => null, // this attr should be filled
        ]);
    }

    /**
     * @test

     */
    public function shouldReturnForbidenWhenUserDoesntHaveRequiredPermissions()
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();

        $endpoint = str_replace('{id}', $this->novelty->id, $this->endpoint);
        $this->json('DELETE', $endpoint)
            ->assertForbidden();
        $this->assertDatabaseHas('novelties', [
            'id' => $this->novelty->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * @test

     */
    public function shouldReturnNotFoundIfNoveltyDoesntExists()
    {
        $endpoint = str_replace('{id}', 111, $this->endpoint);
        $this->json('DELETE', $endpoint)
            ->assertNotFound();
    }
}
