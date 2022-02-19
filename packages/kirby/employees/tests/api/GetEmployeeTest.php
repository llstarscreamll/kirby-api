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
            ->assertJsonHasPath('data.id');
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
