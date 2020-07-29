<?php

namespace Kirby\Novelties\Tests;

use Kirby\Novelties\Models\NoveltyType;

/**
 * Class CreateNoveltyTypeCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateNoveltyTypeCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/novelty-types/';

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
    public function createSuccessfully(ApiTester $I)
    {
        $expectedData = factory(NoveltyType::class)->make([
            'apply_on_time_slots' => [
                ['start' => '08:00', 'end' => '12:00'],
            ],
            'time_zone' => 'America/Bogota',
        ]);

        $I->sendPOST($this->endpoint, $expectedData->toArray());

        $I->seeResponseCodeIs(201);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');

        $I->seeRecord('novelty_types', [
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
    public function shouldReturnUnprocesableEntityWhenCodeIsAlreadyTaken(ApiTester $I)
    {
        factory(NoveltyType::class)->create(['code' => 'foo']);
        $requestPayload = factory(NoveltyType::class)->make(['code' => 'foo']);

        $I->sendPOST($this->endpoint, $requestPayload->toArray());

        $I->seeResponseCodeIs(422);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnForbidenWhenUserDoesntHaveRequiredPermissions(ApiTester $I)
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();

        $expectedData = factory(NoveltyType::class)->make();

        $I->sendPOST($this->endpoint, $expectedData->toArray());

        $I->seeResponseCodeIs(403);
    }
}
