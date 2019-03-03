<?php
namespace Authentication;

use Authentication\ApiTester;

/**
 * Class GetUserCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class GetUserCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/auth/user';

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        $I->amLoggedAsUser();
        $I->haveHttpHeader('Accept', 'application/json');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function whenBearerTokenIsValidExpectAcceptedWithMessage(ApiTester $I)
    {
        $I->sendGET($this->endpoint);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath("$.data.id");
        $I->seeResponseJsonMatchesJsonPath("$.data.name");
        $I->seeResponseJsonMatchesJsonPath("$.data.roles");
        $I->seeResponseJsonMatchesJsonPath("$.data.permissions");
    }
}
