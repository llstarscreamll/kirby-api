<?php
namespace Items;

use Illuminate\Support\Collection;
use Items\ApiTester;
use llstarscreamll\Items\Models\Item;
use llstarscreamll\Users\Models\User;

/**
 * Class FindItemsCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class FindItemsCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/items';

    /**
     * @var \Illuminate\Database\Eloquent\Collection
     */
    private $items;

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        $this->items = factory(Item::class, 4)->create();

        $this->user = $I->amLoggedAsUser(factory(User::class)->create());
        $I->haveHttpHeader('Accept', 'application/json');

    }

    /**
     * @param ApiTester $I
     */
    public function _after(ApiTester $I) {}

    // tests
    /**
     * @param ApiTester $I
     */
    public function requestCriteriaSearchByName(ApiTester $I)
    {
        $I->sendGET($this->endpoint, ['search' => $this->items[0]->name]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseJsonMatchesJsonPath("$.data.0");
        $I->dontSeeResponseJsonMatchesJsonPath("$.data.1");
        $I->dontSeeResponseJsonMatchesJsonPath("$.data.2");
        $I->dontSeeResponseJsonMatchesJsonPath("$.data.3");

    }
}
