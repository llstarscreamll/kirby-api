<?php

namespace Kirby\Employees\Tests\api;

use EmployeesPackageSeed;
use Kirby\Company\Models\CostCenter;
use Kirby\Employees\Models\Employee;
use Kirby\WorkShifts\Models\WorkShift;

/**
 * Class CreateEmployeeTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateEmployeeTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/employees/';

    public function setUp(): void
    {
        parent::setUp();

        $this->seed(EmployeesPackageSeed::class);
        $this->actingAsAdmin($this->user = factory(\Kirby\Users\Models\User::class)->create());
    }

    /**
     * @test
     */
    public function createSuccessfully()
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
            'phone' => '+573219876543',
            'position' => 'designer',
            'salary' => 5000000,
            'cost_center' => $costCenter->toArray(),
            'work_shifts' => [$morningWorkShift->toArray(), $afternoonWorkShift->toArray()],
            'identifications' => [$pinIdentification, $eCardIdentification],
        ];

        $this->json('POST', $this->endpoint, $requestPayload)
            ->assertCreated()
            ->assertJsonHasPath('data.id');

        $employee = Employee::where('code', '987')->first();

        $this->assertDatabaseHas('employees', [
            'code' => '987',
            'identification_number' => '654',
            'location' => 'Medellín',
            'address' => 'Calle 3#2-1',
            'phone' => '+573219876543',
            'position' => 'designer',
            'salary' => 5000000,
            'cost_center_id' => $costCenter->id,
        ]);
        $this->assertDatabaseHas('users', [
            'first_name' => 'Bruce',
            'last_name' => 'Banner',
            'phone_number' => '+573219876543',
            'email' => '987@domain.com',
        ]);
        $this->assertDatabaseHas('employee_work_shift', [
            'employee_id' => $employee->id,
            'work_shift_id' => $morningWorkShift->id,
        ]);
        $this->assertDatabaseHas('employee_work_shift', [
            'employee_id' => $employee->id,
            'work_shift_id' => $afternoonWorkShift->id,
        ]);
        $this->assertDatabaseHas('identifications', ['employee_id' => $employee->id] + $pinIdentification);
        $this->assertDatabaseHas('identifications', ['employee_id' => $employee->id] + $eCardIdentification);
    }

    /**
     * @test
     */
    public function shouldReturnForbidenWhenUserDoesntHaveRequiredPermissions()
    {
        $this->actingAsGuest()->json('POST', $this->endpoint, [])
            ->assertForbidden();
    }
}
