<?php
namespace Shippings;

use llstarscreamll\Shippings\Models\Shipping;
use llstarscreamll\Users\Models\User;
use Shippings\ApiTester;

/**
 * Class FindShippingsCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class FindShippingsCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/shippings';

    /**
     * @var \Illuminate\Database\Eloquent\Collection
     */
    private $shippings;

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        $this->shippings = factory(Shipping::class, 4)->create();

        $this->user = $I->amLoggedAsUser(factory(User::class)->create());
        $I->haveHttpHeader('Accept', 'application/json');

    }

    /**
     * @param ApiTester $I
     */
    public function _after(ApiTester $I) {}

    /**
     * @test
     * @param ApiTester $I
     */
    public function requestCriteriaSearchByName(ApiTester $I)
    {
        $I->sendGET($this->endpoint, ['search' => $this->shippings[0]->name]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseJsonMatchesJsonPath("$.data.0");
        $I->dontSeeResponseJsonMatchesJsonPath("$.data.1");
        $I->dontSeeResponseJsonMatchesJsonPath("$.data.2");
        $I->dontSeeResponseJsonMatchesJsonPath("$.data.3");

    }
}
