<?php

namespace Kirby\Employees\Tests\api;

use Carbon\Carbon;
use EmployeesPackageSeed;
use Illuminate\Support\Facades\Hash;
use Kirby\Authorization\Models\Role;
use Kirby\Company\Models\CostCenter;
use Kirby\Employees\Models\Employee;
use Kirby\Users\Models\User;
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
    public function shouldUpdateEmployeeSuccessfullyWhenInputIsCorrect()
    {
        $employee = factory(Employee::class)->create();
        $employee->user->update(['email' => 'bruce@avengers.com']);
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
            'generate_token' => '15d',
            'work_shifts' => [$morningWorkShift->toArray(), $afternoonWorkShift->toArray()],
            'identifications' => [$pinIdentification, $eCardIdentification],
        ];

        Carbon::setTestNow('2022-06-24 10:10:10');

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
            'email' => 'bruce@avengers.com',
            'first_name' => 'Bruce',
            'last_name' => 'Banner',
            'phone_prefix' => '+57',
            'phone_number' => '3219876543',
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

        $this->assertDatabaseHas('identifications', [
            'employee_id' => $employee->id,
            'type' => 'code',
            'expiration_date' => now()->toDateTimeString(),
        ] + $pinIdentification);

        $this->assertDatabaseHas('identifications', [
            'employee_id' => $employee->id,
            'type' => 'code',
            'expiration_date' => now()->toDateTimeString(),
        ] + $eCardIdentification);

        $this->assertDatabaseHas('identifications', [
            'employee_id' => $employee->id,
            'type' => 'uuid',
            'expiration_date' => now()->addDays(15)->toDateTimeString(),
        ]);
    }

    /**
     * @test
     */
    public function shouldNotUpdatePasswordWhenPasswordIsEmpty()
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
            'email' => 'bruce@avengers.com',
            'password' => '',
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

        $this->json('PUT', str_replace('{id}', $employee->id, $this->endpoint), $requestPayload)
            ->assertOk()
            ->assertJsonHasPath('data.id');

        $user = User::where('email', 'bruce@avengers.com')->first();
        Hash::check('secret', $user->password); // secret is the default password on User factory
    }

    /**
     * @test
     */
    public function shouldReturnUnprocessableEntityWhenEmailIsAlreadyTaken()
    {
        factory(User::class)->create(['email' => 'bruce@avengers.com']);
        $employee = factory(Employee::class)->create();
        $costCenter = factory(CostCenter::class)->create();
        $morningWorkShift = factory(WorkShift::class)->create();
        $pinIdentification = ['name' => 'PIN', 'code' => '123'];

        $requestPayload = [
            'first_name' => 'Bruce',
            'last_name' => 'Banner',
            'email' => 'bruce@avengers.com',
            'password' => 'someP4ssw0rdH3r3!',
            'roles' => [],
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

        $this->json('PUT', str_replace('{id}', $employee->id, $this->endpoint), $requestPayload)
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseMissing('users', [
            'id' => $employee->id,
            'email' => 'bruce@avengers.com',
        ]);
    }

    /**
     * @test
     */
    public function shouldReturnNotFoundIfEmployeeDoesNotExists()
    {
        $requestPayload = [
            'first_name' => 'Bruce',
            'last_name' => 'Banner',
            'email' => 'bruce@avengers.com',
            'password' => 'someP4ssw0rdH3r3!',
            'roles' => [],
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
