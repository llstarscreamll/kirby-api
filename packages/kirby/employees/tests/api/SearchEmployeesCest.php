<?php

namespace Employees;

use Kirby\Employees\Models\Employee;

/**
 * Class SearchEmployeesCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SearchEmployeesCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/employees/';

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
    public function searchSuccessfully(ApiTester $I)
    {
        factory(Employee::class, 5)->create();

        $I->sendGET($this->endpoint);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.0.id');
        $I->seeResponseJsonMatchesJsonPath('$.data.1.id');
        $I->seeResponseJsonMatchesJsonPath('$.data.2.id');
        $I->seeResponseJsonMatchesJsonPath('$.data.3.id');
        $I->seeResponseJsonMatchesJsonPath('$.data.4.id');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnUnprocesableEntityIfUserDoesntHaveRequiredPermissions(ApiTester $I)
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();
        factory(Employee::class, 5)->create();

        $I->sendGET($this->endpoint);

        $I->seeResponseCodeIs(403);
    }
}
