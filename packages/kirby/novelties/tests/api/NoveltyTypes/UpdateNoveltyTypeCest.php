<?php

namespace Novelties;

use Kirby\Novelties\Models\Novelty;
use Kirby\Novelties\Models\NoveltyType;

/**
 * Class UpdateNoveltyTypeCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class UpdateNoveltyTypeCest
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
        $expectedData = factory(NoveltyType::class)->make([
            'apply_on_time_slots' => [
                ['start' => '08:00', 'end' => '12:00'],
            ],
            'time_zone' => 'America/Bogota',
        ]);

        $endpoint = str_replace('{id}', $noveltyTypeId, $this->endpoint);
        $I->sendPUT($endpoint, $expectedData->toArray());

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->canSeeResponseContainsJson(['data' => ['id' => $noveltyTypeId]]);

        $I->seeRecord('novelty_types', [
            'id' => $noveltyTypeId,
            'code' => $expectedData['code'],
            'name' => $expectedData['name'],
            'context_type' => $expectedData['context_type'],
            'time_zone' => $expectedData['time_zone'],
            'apply_on_days_of_type' => $expectedData['apply_on_days_of_type'],
            'apply_on_time_slots' => json_encode($expectedData['apply_on_time_slots']),
            'operator' => $expectedData['operator'],
            'requires_comment' => $expectedData['requires_comment'],
            'keep_in_report' => $expectedData['keep_in_report'],
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnUnprocesableEntityIfUserDoesntHaveRequiredPermissions(ApiTester $I)
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();

        $noveltyTypeId = factory(NoveltyType::class)->create()->id;
        $expectedData = factory(NoveltyType::class)->make();

        $endpoint = str_replace('{id}', $noveltyTypeId, $this->endpoint);
        $I->sendPUT($endpoint, $expectedData->toArray());

        $I->seeResponseCodeIs(403);
    }
}
