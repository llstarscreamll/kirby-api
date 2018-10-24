<?php
namespace Customers;

use Customers\ApiTester;
use llstarscreamll\Customers\Models\Customer;
use llstarscreamll\Users\Models\User;

/**
 * Class FindCustomersCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class FindCustomersCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/customers';

    /**
     * @var \llstarscreamll\Users\Models\User
     */
    private $user;

    /**
     * @var \llstarscreamll\Customers\Models\Customer
     */
    private $customers;

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        $this->customers = factory(Customer::class, 4)->create();

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
        $I->sendGET($this->endpoint, ['search' => $this->customers[0]->name]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseJsonMatchesJsonPath("$.data.0");
        $I->dontSeeResponseJsonMatchesJsonPath("$.data.1");
        $I->dontSeeResponseJsonMatchesJsonPath("$.data.2");
        $I->dontSeeResponseJsonMatchesJsonPath("$.data.3");
    }
}
