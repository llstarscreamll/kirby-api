<?php

namespace Kirby\Employees\Tests\api;

use EmployeesPackageSeed;
use Kirby\Employees\Models\Employee;

/**
 * Class GetEmployeeTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 *
 * @internal
 */
class GetEmployeeTest extends \Tests\TestCase
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

        $this->json('GET', str_replace('{id}', $employee->id, $this->endpoint))
            ->assertOk()
            ->assertJsonPath('data.id', $employee->id)
            ->assertJsonPath('data.first_name', $employee->first_name)
            ->assertJsonPath('data.last_name', $employee->last_name)
            ->assertJsonPath('data.email', $employee->email)
            ->assertJsonPath('data.roles', $employee->user->roles->toArray())
            ->assertJsonPath('data.cost_center_id', $employee->cost_center_id)
            ->assertJsonPath('data.code', $employee->code)
            ->assertJsonPath('data.identification_number', $employee->identification_number)
            ->assertJsonPath('data.position', $employee->position)
            ->assertJsonPath('data.location', $employee->location)
            ->assertJsonPath('data.address', $employee->address)
            ->assertJsonPath('data.phone_prefix', $employee->phone_prefix)
            ->assertJsonPath('data.phone', $employee->phone)
            ->assertJsonPath('data.salary', (int) $employee->salary)
            ->assertJsonPath('data.cost_center', $employee->costCenter->toArray())
            ->assertJsonPath('data.work_shifts', $employee->workShifts->toArray())
            ->assertJsonPath('data.identifications', $employee->identifications->toArray())
            ->assertJsonPath('data.created_at', $employee->created_at->toIso8601String())
            ->assertJsonPath('data.updated_at', $employee->updated_at->toIso8601String())
            ->assertJsonPath('data.deleted_at', $employee->deleted_at);
    }

    /**
     * @test
     */
    public function shouldReturnNotFoundIfEmployeeDoesNotExists()
    {
        $this->json('GET', str_replace('{id}', 999, $this->endpoint))
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

        $this->json('GET', str_replace('{id}', $employee->id, $this->endpoint))
            ->assertForbidden();
    }
}
