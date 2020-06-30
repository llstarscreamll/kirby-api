<?php

namespace Novelties;

use Kirby\Novelties\Models\Novelty;
use Kirby\Novelties\Models\NoveltyType;

/**
 * Class DeleteNoveltyTypeCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class DeleteNoveltyTypeCest
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
        $noveltyTypeId = factory(NoveltyType::class)->create()->id;

        // novelty type should exists but isn't soft deleted
        $I->seeRecord('novelty_types', [
            'id' => $noveltyTypeId,
            'deleted_at' => null,
        ]);

        $endpoint = str_replace('{id}', $noveltyTypeId, $this->endpoint);
        $I->sendDELETE($endpoint);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data');
        $I->canSeeResponseContainsJson(['data' => 'ok']);

        // novelty type should be soft deleted
        $I->seeRecord('novelty_types', ['id' => $noveltyTypeId]);
        $I->dontSeeRecord('novelty_types', [
            'id' => $noveltyTypeId,
            'deleted_at' => null,
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

        $noveltyTypeId = factory(NoveltyType::class)->create()->id;

        $endpoint = str_replace('{id}', $noveltyTypeId, $this->endpoint);
        $I->sendDELETE($endpoint);

        $I->seeResponseCodeIs(403);
    }
}
