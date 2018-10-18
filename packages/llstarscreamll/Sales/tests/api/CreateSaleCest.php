<?php
namespace Sales;

use Sales\ApiTester;

/**
 * Class CreateSaleCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateSaleCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/sales/create';

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I) {}

    /**
     * @param ApiTester $I
     */
    public function _after(ApiTester $I) {}

    /**
     * @test
     * @param ApiTester $I
     */
    public function tryToCreateSale(ApiTester $I)
    {
        $data = [];
        $I->sendPOST($this->endpoint, $data);

        $I->seeResponseCodeIs(201);
    }
}
