<?php

namespace Kirby\Novelties\Tests;

use Kirby\Novelties\Models\Novelty;

/**
 * Class GetNoveltyCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class GetNoveltyCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/novelties/{id}';

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        $this->user = $I->amLoggedAsAdminUser();
        $I->haveHttpHeader('Accept', 'application/json');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function getNoveltySuccessfully(ApiTester $I)
    {
        $novelty = factory(Novelty::class)->create();
        $endpoint = str_replace('{id}', $novelty->id, $this->endpoint);

        $I->sendGET($endpoint);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeResponseJsonMatchesJsonPath('$.data.time_clock_log_id');
        $I->seeResponseJsonMatchesJsonPath('$.data.employee_id');
        $I->seeResponseJsonMatchesJsonPath('$.data.novelty_type_id');
        $I->seeResponseJsonMatchesJsonPath('$.data.novelty_type');
        $I->seeResponseJsonMatchesJsonPath('$.data.employee');
        $I->seeResponseJsonMatchesJsonPath('$.data.time_clock_log');
        $I->seeResponseJsonMatchesJsonPath('$.data.approvals');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnForbidenWhenUserDoesntHaveRequiredPermissions(ApiTester $I)
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();
        $novelty = factory(Novelty::class)->create();
        $endpoint = str_replace('{id}', $novelty->id, $this->endpoint);

        $I->sendGET($endpoint);

        $I->seeResponseCodeIs(403);
    }
}
