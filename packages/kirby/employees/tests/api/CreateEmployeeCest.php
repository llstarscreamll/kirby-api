<?php

namespace Employees;

use Illuminate\Support\Facades\DB;
use Kirby\Company\Models\CostCenter;
use Kirby\Employees\Models\Employee;
use Kirby\WorkShifts\Models\WorkShift;

/**
 * Class CreateEmployeeCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateEmployeeCest
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
    public function createSuccessfully(ApiTester $I)
    {
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

        $I->sendPOST($this->endpoint, $requestPayload);

        $employee = Employee::where('code', '987')->first();

        $I->seeResponseCodeIs(201);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeRecord('employees', [
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
            'email' => '987@domain.com'
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
    public function shouldReturnUnprocesableEntityIfUserDoesntHaveRequiredPermissions(ApiTester $I)
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();
        $employeeData = factory(Employee::class)->make();

        $I->sendPOST($this->endpoint, $employeeData);

        $I->seeResponseCodeIs(403);
    }
}
