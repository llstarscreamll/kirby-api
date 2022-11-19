<?php

namespace Kirby\Production\Tests\Feature\API\V1;

use Kirby\Authorization\Models\Permission;
use Kirby\Customers\Models\Customer;
use Kirby\Employees\Models\Employee;
use Kirby\Employees\Models\Identification;
use Kirby\Machines\Models\Machine;
use Kirby\Production\Enums\Purpose;
use Kirby\Production\Enums\Tag;
use Kirby\Products\Models\Product;
use Kirby\Users\Models\User;
use ProductionPackageSeed;
use Tests\TestCase;

/**
 * @internal
 */
class CreateProductionLogTest extends TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/production-logs';

    /**
     * @var string
     */
    private $method = 'POST';

    private User $user;
    private Employee $employee;
    private Machine $machine;
    private Product $product;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ProductionPackageSeed::class);
        $this->actingAsAdmin($this->user = factory(Employee::class)->create()->user);
        $this->employee = factory(Employee::class)->create();
        $this->employee->user->permissions()->sync(Permission::all());
        $this->machine = factory(Machine::class)->create();
        $this->product = factory(Product::class)->create();
        $this->customer = factory(Customer::class)->create();
    }

    /**
     * Debe persistir los datos correctamente cuando los datos están correctos.
     * El usuario autenticado tiene todos los permisos.
     *
     * @test
     */
    public function shouldBeCreatedSuccessfullyWhenDataIsCorrectAndUserHasCreateOnBehalfOfAnotherEmployeePermission()
    {
        $payload = [
            'employee_code' => ($identification = factory(Identification::class)->create(['type' => 'uuid', 'employee_id' => $this->employee]))->code, // another employee
            'product_id' => $this->product->id,
            'machine_id' => $this->machine->id,
            'customer_id' => $this->customer->id,
            'purpose' => Purpose::Sales,
            'batch' => 123456,
            'tare_weight' => 10.5,
            'gross_weight' => 25.8,
        ];

        $this->json($this->method, $this->endpoint, $payload)->assertOk();

        $this->assertDatabaseHas('production_logs', [
            'product_id' => $this->product->id,
            'employee_id' => $identification->employee_id,
            'machine_id' => $this->machine->id,
            'customer_id' => $this->customer->id,
            'purpose' => Purpose::Sales,
            'tag' => Tag::InLine, // default value when created
            'tag_updated_at' => now()->toDateTimeString(), // por defecto la fecha de creación del registro
            'batch' => 123456,
            'tare_weight' => 10.5,
            'gross_weight' => 25.8,
        ]);
    }

    /**
     * Debe persistir los datos correctamente cuando los datos están correctos.
     * El usuario autenticado tiene todos los permisos.
     *
     * @test
     */
    public function shouldReturnErrorWhenDataIsCorrectButTokenOwnerDoesNotHaveCreateProductionLogPermission()
    {
        $payload = [
            'employee_code' => ($identification = factory(Identification::class)->create(['type' => 'uuid']))->code, // employee without permissions
            'product_id' => $this->product->id,
            'machine_id' => $this->machine->id,
            'customer_id' => $this->customer->id,
            'purpose' => Purpose::Sales,
            'batch' => 123456,
            'tare_weight' => 10.5,
            'gross_weight' => 25.8,
        ];

        $this->json($this->method, $this->endpoint, $payload)
            ->assertStatus(400)
            ->assertJsonPath('errors.0.title', 'Permisos insuficientes.')
            ->assertJsonPath('errors.0.detail', 'El dueño del token no tiene los suficientes permisos para realizar esta acción.');

        $this->assertDatabaseMissing('production_logs', [
            'product_id' => $this->product->id,
            'employee_id' => $identification->employee_id,
        ]);
    }

    /**
     * Cuando el usuario no tiene permisos para crear registros de producción a
     * nombre de otro empleado, se debe asociar los registros a sí mismo.
     *
     * @test
     */
    public function shouldCreatedSuccessfullyWhenUserDoesNotHaveCreateOnBehalfOfAnotherEmployeePermission()
    {
        $payload = [
            'product_id' => $this->product->id,
            'machine_id' => $this->machine->id,
            'customer_id' => $this->customer->id,
            'purpose' => Purpose::Sales,
            'batch' => 123456,
            'tare_weight' => 10.5,
            'gross_weight' => 25.8,
        ];

        // remove permission
        $this->user->revokePermissionTo('production-logs.create-on-behalf-of-another-person');

        $this->json($this->method, $this->endpoint, $payload)->assertOk();

        // as user does not have permission employee_id should be equals to
        // current user employee id
        $this->assertDatabaseHas('production_logs', [
            'product_id' => $this->product->id,
            'employee_id' => $this->user->id,
        ]);
    }

    /**
     * Debe crear los registros correctamente cuando el empleado tiene permisos
     * para crear registros de producción a nombre de otros empleados.
     *
     * @test
     */
    public function shouldCreatedSuccessfullyWhenHasCreateOnBehalfOfAnotherPersonPermission()
    {
        $payload = [
            'employee_code' => ($identification = factory(Identification::class)->create(['type' => 'uuid', 'employee_id' => $this->employee]))->code, // another employee
            'product_id' => $this->product->id,
            'machine_id' => $this->machine->id,
            'customer_id' => $this->customer->id,
            'purpose' => Purpose::Sales,
            'batch' => 123456,
            'tare_weight' => 10.5,
            'gross_weight' => 25.8,
        ];

        $this->json($this->method, $this->endpoint, $payload)->assertOk();

        $this->assertDatabaseHas('production_logs', [
            'product_id' => $this->product->id,
            'employee_id' => $identification->employee_id,
        ]);
    }

    /**
     * Debe permitir crear el registro cuando los campos lote y cliente no están
     * presentes.
     *
     * @test
     */
    public function shouldBeCreatedSuccessfullyWhenBatchAndCustomerAreMissing()
    {
        $payload = [
            'product_id' => $this->product->id,
            'machine_id' => $this->machine->id,
            'purpose' => Purpose::Sales,
            'tare_weight' => 10.5,
            'gross_weight' => 25.8,
        ];

        $this->user->revokePermissionTo('production-logs.create-on-behalf-of-another-person');

        $this->json($this->method, $this->endpoint, $payload)->assertOk();

        $this->assertDatabaseHas('production_logs', [
            'product_id' => $this->product->id,
            'employee_id' => $this->user->id,
            'machine_id' => $this->machine->id,
            'customer_id' => null,
            'batch' => null,
            'tare_weight' => 10.5,
            'gross_weight' => 25.8,
        ]);
    }

    /**
     * Debe validar que los códigos/IDs otorgados de las entidades existan.
     *
     * @test
     */
    public function shouldValidateThatEntitiesIDsAndCodeExist()
    {
        $payload = [
            'employee_code' => 999,
            'product_id' => 999,
            'machine_id' => 999,
            'purpose' => Purpose::Sales,
            'tare_weight' => 10.5,
            'gross_weight' => 25.8,
        ];

        $this->json($this->method, $this->endpoint, $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['employee_code', 'product_id', 'machine_id']);
    }

    /**
     * Debe validar que los pesos sean valores numéricos.
     *
     * @test
     */
    public function shouldReturnUnprocessableEntityWhenWeightsAreNotNumeric()
    {
        $payload = [
            'product_id' => $this->product->id,
            'machine_id' => $this->machine->id,
            'tare_weight' => 'ABC',
            'gross_weight' => 'DEF',
            'batch' => 'GHI',
        ];

        $this->json($this->method, $this->endpoint, $payload)
            ->assertJsonValidationErrors(['tare_weight', 'gross_weight', 'batch']);
    }

    /**
     * Debe validar que el peso tara sea mayor al peso bruto.
     *
     * @test
     */
    public function shouldValidateThatGrossWeightIsGreaterThanTareWeight()
    {
        $payload = [
            'product_id' => $this->product->id,
            'machine_id' => $this->machine->id,
            'tare_weight' => 10.5,
            'gross_weight' => 10.5,
            'batch' => 123,
        ];

        $this->json($this->method, $this->endpoint, $payload)
            ->assertJsonValidationErrors(['gross_weight']);
    }

    /**
     * Debe validar que el destino sea un campo obligatorio.
     *
     * @test
     */
    public function shouldValidateThatPurposeFieldIsRequired()
    {
        $payload = [
            'product_id' => $this->product->id,
            'machine_id' => $this->machine->id,
            'tare_weight' => 5,
            'gross_weight' => 10.5,
        ];

        $this->json($this->method, $this->endpoint, $payload)
            ->assertJsonValidationErrors(['purpose']);
    }

    /**
     * Debe restringir acceso cuando el usuario no tiene permisos.
     *
     * @test
     */
    public function shouldReturnForbiddenWhenUserDoesNotHavePermissions()
    {
        $this->user->permissions()->delete();

        $this->json($this->method, $this->endpoint, [])->assertForbidden();
    }
}
