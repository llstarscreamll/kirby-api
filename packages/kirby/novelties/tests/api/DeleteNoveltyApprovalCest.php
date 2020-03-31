<?php

namespace Novelties;

use Illuminate\Support\Facades\Artisan;
use Kirby\Novelties\Models\Novelty;
use NoveltiesPermissionsSeeder;

/**
 * Class DeleteNoveltyApprovalCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class DeleteNoveltyApprovalCest
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

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {

        Artisan::call('db:seed', ['--class' => NoveltiesPermissionsSeeder::class]);
        $this->user = $I->amLoggedAsAdminUser();
        $this->novelties = factory(Novelty::class, 2)->create();
        $this->approvalId = $I->haveRecord('novelty_approvals', [
            'user_id' => $this->user->id,
            'novelty_id' => $this->novelties->first()->id,
        ]);

        $I->haveHttpHeader('Accept', 'application/json');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldDeleteApprovalSuccessfully(ApiTester $I)
    {
        $endpoint = str_replace(
            ['{novelty-id}', '{approval-id}'],
            [$this->novelties->first()->id, $this->approvalId],
            $this->endpoint
        );
        $I->sendDELETE($endpoint);

        $I->seeResponseCodeIs(200);
        $I->dontSeeRecord('novelty_approvals', [
            'user_id' => $this->user->id,
            'novelty_id' => $this->novelties->first()->id,
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

        $endpoint = str_replace(
            ['{novelty-id}', '{approval-id}'],
            [$this->novelties->first()->id, $this->approvalId],
            $this->endpoint
        );
        $I->sendDELETE($endpoint);

        $I->seeResponseCodeIs(403);
        $I->seeRecord('novelty_approvals', [
            'user_id' => $this->user->id,
            'novelty_id' => $this->novelties->first()->id,
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnNotFoundIfTimeClockLogDoesntExists(ApiTester $I)
    {
        $endpoint = str_replace('{novelty-id}', 111, $this->endpoint);
        $I->sendDELETE($endpoint);

        $I->seeResponseCodeIs(404);
    }
}
