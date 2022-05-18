<?php

namespace Kirby\Employees\Tests\api;

use EmployeesPackageSeed;
use Illuminate\Support\Facades\Hash;
use Kirby\Authorization\Models\Role;
use Kirby\Company\Models\CostCenter;
use Kirby\Employees\Models\Employee;
use Kirby\Users\Models\User;
use Kirby\WorkShifts\Models\WorkShift;

/**
 * Class CreateEmployeeTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 *
 * @internal
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
            'email' => 'bruce@avengers.com',
            'password' => 'someP4ssw0rdH3r3!',
            'roles' => [Role::create(['name' => 'admin']), Role::create(['name' => 'reader'])],
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

        $this->json('POST', $this->endpoint, $requestPayload)
            ->assertCreated()
            ->assertJsonHasPath('data.id');

        $employee = Employee::where('code', '987')->first();

        $this->assertDatabaseHas('employees', [
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
            'email' => 'bruce@avengers.com',
        ]);

        $user = User::where('email', 'bruce@avengers.com')->first();
        $this->assertTrue(Hash::check('someP4ssw0rdH3r3!', $user->password));
        $this->assertTrue($user->hasRole('admin'), 'Admin role assigned');
        $this->assertTrue($user->hasRole('reader'), 'Reader role assigned');

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

    /** @test */
    public function shouldReturnUnprocessableEntityWhenEmailIsAlreadyTaken()
    {
        factory(User::class)->create(['email' => 'bruce@avengers.com']);
        $costCenter = factory(CostCenter::class)->create();
        $morningWorkShift = factory(WorkShift::class)->create();
        $pinIdentification = ['name' => 'PIN', 'code' => '123'];

        $requestPayload = [
            'first_name' => 'Bruce',
            'last_name' => 'Banner',
            'email' => 'bruce@avengers.com',
            'password' => 'someP4ssw0rdH3r3!',
            'roles' => [Role::create(['name' => 'admin']), Role::create(['name' => 'reader'])],
            'code' => '987',
            'identification_number' => '654',
            'location' => 'Medellín',
            'address' => 'Calle 3#2-1',
            'phone_prefix' => '+57',
            'phone' => '3219876543',
            'position' => 'designer',
            'salary' => 5000000,
            'cost_center' => $costCenter->toArray(),
            'work_shifts' => [$morningWorkShift->toArray()],
            'identifications' => [$pinIdentification],
        ];

        $this
            ->json('POST', $this->endpoint, $requestPayload)
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseMissing('employees', [
            'code' => '987',
            'identification_number' => '654',
        ]);

        $this->assertDatabaseMissing('users', [
            'first_name' => 'Bruce',
            'last_name' => 'Banner',
            'email' => 'bruce@avengers.com',
        ]);
    }

    /**
     * @test
     */
    public function shouldReturnForbiddenWhenUserDoesntHaveRequiredPermissions()
    {
        $this->actingAsGuest()->json('POST', $this->endpoint, [])
            ->assertForbidden();
    }
}
