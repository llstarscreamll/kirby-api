<?php

namespace Company;

use llstarscreamll\Company\Models\SubCostCenter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SearchSubCostCentersCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SearchSubCostCentersCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/sub-cost-centers';

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        $I->amLoggedAsAdminUser();
        $I->haveHttpHeader('Accept', 'application/json');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function withoutQueryString(ApiTester $I)
    {
        factory(SubCostCenter::class, 5)->create();

        $I->sendGET($this->endpoint);

        $I->seeResponseCodeIs(Response::HTTP_OK);
        $I->seeResponseJsonMatchesJsonPath('$.data.0.id');
        $I->seeResponseJsonMatchesJsonPath('$.meta');
        $I->seeResponseJsonMatchesJsonPath('$.links');
    }
}
