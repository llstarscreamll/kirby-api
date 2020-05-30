<?php

namespace Employees;

use Kirby\Employees\Models\Employee;

/**
 * Class GetEmployeeCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class GetEmployeeCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/employees/{id}';

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
        $employee = factory(Employee::class)->create();

        $I->sendGET(str_replace('{id}', $employee->id, $this->endpoint));

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnNotFoundIfEmployeeDoesNotExists(ApiTester $I)
    {
        $I->sendGET(str_replace('{id}', 999, $this->endpoint));

        $I->seeResponseCodeIs(404);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnUnprocesableEntityIfUserDoesntHaveRequiredPermissions(ApiTester $I)
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();
        $employee = factory(Employee::class)->create();

        $I->sendGET(str_replace('{id}', $employee->id, $this->endpoint));

        $I->seeResponseCodeIs(403);
    }
}
