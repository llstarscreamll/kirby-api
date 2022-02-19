<?php

namespace Kirby\Employees\Tests\api;

use EmployeesPackageSeed;
use Kirby\Company\Models\CostCenter;
use Kirby\Employees\Models\Employee;
use Kirby\WorkShifts\Models\WorkShift;

/**
 * Class UpdateEmployeeTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 *
 * @internal
 */
class UpdateEmployeeTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/employees/{id}';

    public function setUp(): void
    {
        parent::setUp();
        $this->seed(EmployeesPackageSeed::class);
        $this->actingAsAdmin($this->user = factory(\Kirby\Users\Models\User::class)->create());
    }

    /**
     * @test
     */
    public function getSuccessfully()
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
            'phone_prefix' => '+57',
            'phone' => '3219876543',
            'position' => 'designer',
            'salary' => 5000000,
            'cost_center' => $costCenter->toArray(),
            'work_shifts' => [$morningWorkShift->toArray(), $afternoonWorkShift->toArray()],
            'identifications' => [$pinIdentification, $eCardIdentification],
        ];

        $this->json('PUT', str_replace('{id}', $employee->id, $this->endpoint), $requestPayload)
            ->assertOk()
            ->assertJsonHasPath('data.id');

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'code' => '987',
            'identification_number' => '654',
            'location' => 'Medellín',
            'address' => 'Calle 3#2-1',
            'position' => 'designer',
            'salary' => 5000000,
            'cost_center_id' => $costCenter->id,
        ]);

        $this->assertDatabaseHas('users', [
            'first_name' => 'Bruce',
            'last_name' => 'Banner',
            'phone_prefix' => '+57',
            'phone_number' => '3219876543',
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
    public function shouldReturnNotFoundIfEmployeeDoesNotExists()
    {
        $requestPayload = [
            'first_name' => 'Bruce',
            'last_name' => 'Banner',
            'code' => '987',
            'identification_number' => '654',
            'location' => 'Medellín',
            'address' => 'Calle 3#2-1',
            'phone_prefix' => '+57',
            'phone' => '3219876543',
            'position' => 'designer',
            'salary' => 5000000,
            'cost_center' => factory(CostCenter::class)->create(),
            'work_shifts' => [factory(WorkShift::class)->create()],
            'identifications' => [['name' => 'PIN', 'code' => '123']],
        ];

        $this->json('PUT', str_replace('{id}', 999, $this->endpoint), $requestPayload)
            ->assertNotFound();
    }

    /**
     * @test
     */
    public function shouldReturnForbiddenWhenUserDoesntHaveRequiredPermissions()
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();
        $employee = factory(Employee::class)->create();

        $this->json('PUT', str_replace('{id}', $employee->id, $this->endpoint))
            ->assertForbidden();
    }
}
