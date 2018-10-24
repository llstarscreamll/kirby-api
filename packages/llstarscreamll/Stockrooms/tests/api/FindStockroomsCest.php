<?php
namespace Stockrooms;

use llstarscreamll\Stockrooms\Models\Stockroom;
use llstarscreamll\Users\Models\User;
use Stockrooms\ApiTester;

/**
 * Class FindStockroomsCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class FindStockroomsCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/stockrooms';

    /**
     * @var \Illuminate\Database\Eloquent\Collection
     */
    private $stockrooms;

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        $this->stockrooms = factory(Stockroom::class, 4)->create();

        $this->user = $I->amLoggedAsUser(factory(User::class)->create());
        $I->haveHttpHeader('Accept', 'application/json');
    }

    /**
     * @param ApiTester $I
     */
    public function _after(ApiTester $I) {}

    /**
     * @param ApiTester $I
     */
    public function requestCriteriaSearchByName(ApiTester $I)
    {
        $I->sendGET($this->endpoint, ['search' => $this->stockrooms[0]->name]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseJsonMatchesJsonPath("$.data.0");
        $I->dontSeeResponseJsonMatchesJsonPath("$.data.1");
        $I->dontSeeResponseJsonMatchesJsonPath("$.data.2");
        $I->dontSeeResponseJsonMatchesJsonPath("$.data.3");

    }
}
