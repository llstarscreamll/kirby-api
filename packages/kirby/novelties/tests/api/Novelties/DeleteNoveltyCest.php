<?php

namespace Novelties;

use Illuminate\Support\Facades\Artisan;
use Kirby\Novelties\Models\Novelty;
use NoveltiesPermissionsSeeder;

/**
 * Class DeleteNoveltyCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class DeleteNoveltyCest
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

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        Artisan::call('db:seed', ['--class' => NoveltiesPermissionsSeeder::class]);
        $this->novelty = factory(Novelty::class)->create();

        $this->user = $I->amLoggedAsAdminUser();
        $I->haveHttpHeader('Accept', 'application/json');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldDeleteNoveltySuccessfully(ApiTester $I)
    {
        $endpoint = str_replace(
            '{id}',
            $this->novelty->id,
            $this->endpoint
        );
        $I->sendDELETE($endpoint);

        $I->seeResponseCodeIs(200);
        $I->dontSeeRecord('novelties', [
            'id' => $this->novelty->id,
            'deleted_at' => null, // this attr should be filled
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnUnathorizedIfUserDoesntHaveRequiredPermission(ApiTester $I)
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();

        $endpoint = str_replace('{id}', $this->novelty->id, $this->endpoint);
        $I->sendDELETE($endpoint);

        $I->seeResponseCodeIs(403);
        $I->seeRecord('novelties', [
            'id' => $this->novelty->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnNotFoundIfNoveltyDoesntExists(ApiTester $I)
    {
        $endpoint = str_replace('{id}', 111, $this->endpoint);
        $I->sendDELETE($endpoint);

        $I->seeResponseCodeIs(404);
    }
}
