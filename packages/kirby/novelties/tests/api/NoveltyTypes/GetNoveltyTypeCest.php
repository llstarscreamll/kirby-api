<?php

namespace Novelties;

use Kirby\Novelties\Models\NoveltyType;

/**
 * Class GetNoveltyTypeCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class GetNoveltyTypeCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/novelty-types/{id}';

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
    public function getSuccessfully(ApiTester $I)
    {
        $expectedNoveltyType = factory(NoveltyType::class)->create();

        $endpoint = str_replace('{id}', $expectedNoveltyType->id, $this->endpoint);
        $I->sendGET($endpoint);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->canSeeResponseContainsJson(['data' => ['id' => $expectedNoveltyType->id]]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnForbidenWhenUserDoesntHaveRequiredPermissions(ApiTester $I)
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();

        $I->sendGET($this->endpoint);

        $I->seeResponseCodeIs(403);
    }
}
