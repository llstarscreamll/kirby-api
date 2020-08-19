<?php

namespace Employees;

use EmployeesPackageSeed;
use Kirby\Employees\Models\Employee;

/**
 * Class SearchEmployeesTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SearchEmployeesTest extends \Tests\TestCase
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
    public function searchSuccessfully()
    {
        factory(Employee::class, 5)->create();

        $this->json('GET', $this->endpoint)
            ->assertOk()
            ->assertJsonHasPath('data.0.id')
            ->assertJsonHasPath('data.1.id')
            ->assertJsonHasPath('data.2.id')
            ->assertJsonHasPath('data.3.id')
            ->assertJsonHasPath('data.4.id');
    }

    /**
     * @test
     */
    public function shouldReturnForbidenWhenUserDoesntHaveRequiredPermissions()
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();
        factory(Employee::class, 5)->create();

        $this->json('GET', $this->endpoint)
            ->assertForbidden();
    }
}
