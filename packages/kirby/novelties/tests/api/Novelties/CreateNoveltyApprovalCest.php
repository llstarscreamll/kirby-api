<?php

namespace Kirby\Novelties\Tests;

use Illuminate\Support\Facades\Artisan;
use Kirby\Novelties\Models\Novelty;
use NoveltiesPermissionsSeeder;

/**
 * Class CreateNoveltyApprovalCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateNoveltyApprovalCest
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

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        Artisan::call('db:seed', ['--class' => NoveltiesPermissionsSeeder::class]);
        $this->user = $I->amLoggedAsAdminUser();
        $this->novelties = factory(Novelty::class, 2)->create();

        $I->haveHttpHeader('Accept', 'application/json');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldCreateApprovalSuccessfully(ApiTester $I)
    {
        $novelty = $this->novelties->first();
        $endpoint = str_replace('{novelty-id}', $novelty->id, $this->endpoint);
        $I->sendPOST($endpoint);

        $I->seeResponseCodeIs(201);
        $I->seeRecord('novelty_approvals', [
            'user_id' => $this->user->id,
            'novelty_id' => $novelty->id,
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnForbidenWhenUserDoesntHaveRequiredPermissions(ApiTester $I)
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();

        $endpoint = str_replace('{novelty-id}', $this->novelties->first()->id, $this->endpoint);
        $I->sendPOST($endpoint);

        $I->seeResponseCodeIs(403);
        $I->dontSeeRecord('novelty_approvals', [
            'user_id' => $this->user->id,
            'novelty_id' => $this->novelties->first()->id,
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnNotFoundIfNoveltyDoesntExists(ApiTester $I)
    {
        $endpoint = str_replace('{novelty-id}', 111, $this->endpoint);
        $I->sendPOST($endpoint);

        $I->seeResponseCodeIs(404);
    }
}
