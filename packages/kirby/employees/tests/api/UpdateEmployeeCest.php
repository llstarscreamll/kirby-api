<?php

namespace Employees;

use Kirby\Company\Models\CostCenter;
use Kirby\Employees\Models\Employee;
use Kirby\WorkShifts\Models\WorkShift;

/**
 * Class UpdateEmployeeCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class UpdateEmployeeCest
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
        $costCenter = factory(CostCenter::class)->create();
        $morningWorkShift = factory(WorkShift::class)->create();
        $afternoonWorkShift = factory(WorkShift::class)->create();
        $pinIdentification = ['name' => 'PIN', 'code' => '123'];
        $eCardIdentification = ['name' => 'E-card', 'code' => 'Code-3'];

        $requestPayload = [
            'first_name' => 'Bruce',
            'last_name' => 'Banner',
            'code' => '987',
            'identification_number' => '654',
            'location' => 'Medellín',
            'address' => 'Calle 3#2-1',
            'phone' => '3219876543',
            'position' => 'designer',
            'salary' => 5000000,
            'cost_center' => $costCenter->toArray(),
            'work_shifts' => [$morningWorkShift->toArray(), $afternoonWorkShift->toArray()],
            'identifications' => [$pinIdentification, $eCardIdentification],
        ];

        $I->sendPUT(str_replace('{id}', $employee->id, $this->endpoint), $requestPayload);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('employees', [
            'id' => $employee->id,
            'code' => '987',
            'identification_number' => '654',
            'location' => 'Medellín',
            'address' => 'Calle 3#2-1',
            'phone' => '3219876543',
            'position' => 'designer',
            'salary' => 5000000,
            'cost_center_id' => $costCenter->id,
        ]);
        $I->seeRecord('users', [
            'first_name' => 'Bruce',
            'last_name' => 'Banner',
        ]);
        $I->seeRecord('employee_work_shift', [
            'employee_id' => $employee->id,
            'work_shift_id' => $morningWorkShift->id,
        ]);
        $I->seeRecord('employee_work_shift', [
            'employee_id' => $employee->id,
            'work_shift_id' => $afternoonWorkShift->id,
        ]);
        $I->seeRecord('identifications', ['employee_id' => $employee->id] + $pinIdentification);
        $I->seeRecord('identifications', ['employee_id' => $employee->id] + $eCardIdentification);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnNotFoundIfEmployeeDoesNotExists(ApiTester $I)
    {
        $requestPayload = [
            'first_name' => 'Bruce',
            'last_name' => 'Banner',
            'code' => '987',
            'identification_number' => '654',
            'location' => 'Medellín',
            'address' => 'Calle 3#2-1',
            'phone' => '3219876543',
            'position' => 'designer',
            'salary' => 5000000,
            'cost_center' => factory(CostCenter::class)->create(),
            'work_shifts' => [factory(WorkShift::class)->create()],
            'identifications' => [['name' => 'PIN', 'code' => '123']],
        ];

        $I->sendPUT(str_replace('{id}', 999, $this->endpoint), $requestPayload);

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

        $I->sendPUT(str_replace('{id}', $employee->id, $this->endpoint));

        $I->seeResponseCodeIs(403);
    }
}
