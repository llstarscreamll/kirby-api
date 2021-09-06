<?php

namespace Kirby\Production\Tests\Feature\API\V1;

use Kirby\Customers\Models\Customer;
use Kirby\Employees\Models\Employee;
use Kirby\Machines\Models\Machine;
use Kirby\Products\Models\Product;
use Kirby\Users\Models\User;
use ProductionPackageSeed;
use Tests\TestCase;

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

    /**
     * @var \Kirby\Users\Models\User
     */
    private $user;

    /**
     * @var \Kirby\Employees\Models\Employee
     */
    private $employee;

    /**
     * @var \Kirby\Machines\Models\Machine
     */
    private $machine;

    /**
     * @var \Kirby\Products\Models\Product
     */
    private $product;

    /**
     * @var \Kirby\Customers\Models\Customer
     */
    private $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ProductionPackageSeed::class);
        $this->actingAsAdmin($this->user = factory(User::class)->create());
        $this->employee = factory(Employee::class)->create(['id' => $this->user->id]);
        $this->machine = factory(Machine::class)->create();
        $this->product = factory(Product::class)->create();
        $this->customer = factory(Customer::class)->create();
    }

    /**
     * Debe persistir los datos correctamente cuando los datos están correctos.
     *
     * @test
     */
    public function shouldBeCreatedSuccessfullyWhenDataIsCorrect()
    {
        $payload = [
            'product_id' => $this->product->id,
            'machine_id' => $this->machine->id,
            'customer_id' => $this->customer->id,
            'batch' => 123456,
            'tare_weight' => 10.5,
            'gross_weight' => 25.8,
        ];

        $this->json($this->method, $this->endpoint, $payload)->assertOk();

        $this->assertDatabaseHas('production_logs', [
            'product_id' => $this->product->id,
            'employee_id' => $this->user->employee->id,
            'machine_id' => $this->machine->id,
            'customer_id' => $this->customer->id,
            'batch' => 123456,
            'tare_weight' => 10.5,
            'gross_weight' => 25.8,
        ]);
    }

    /**
     * Cuando el usuario no tiene permisos para crear registros de producción a
     * nombre de otro empleado, se debe asociar los registros a sí mismo.
     *
     * @test
     */
    public function shouldCreatedSuccesfulyWhenDoesNotHaveCreateOnBehalfOfAnotherPersonPermission()
    {
        $payload = [
            'employee_id' => factory(Employee::class)->create()->id, // another employee
            'product_id' => $this->product->id,
            'machine_id' => $this->machine->id,
            'customer_id' => $this->customer->id,
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
    public function shouldCreatedSuccesfulyWhenHasCreateOnBehalfOfAnotherPersonPermission()
    {
        $payload = [
            'employee_id' => ($expectedEmployee = factory(Employee::class)->create())->id, // another employee
            'product_id' => $this->product->id,
            'machine_id' => $this->machine->id,
            'customer_id' => $this->customer->id,
            'batch' => 123456,
            'tare_weight' => 10.5,
            'gross_weight' => 25.8,
        ];

        $this->json($this->method, $this->endpoint, $payload)->assertOk();

        // as user does not have permission employee_id should be equals to
        // current user employee id
        $this->assertDatabaseHas('production_logs', [
            'product_id' => $this->product->id,
            'employee_id' => $expectedEmployee->id,
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
            'tare_weight' => 10.5,
            'gross_weight' => 25.8,
        ];

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
     * Debe validar que los ids otorgados de empleado, producto y máquina
     * existan en la base de datos.
     *
     * @test
     */
    public function shouldValidateThatProductMachineAndEmployeeExist()
    {
        $payload = [
            'employee_id' => 999,
            'product_id' => 999,
            'machine_id' => 999,
            'tare_weight' => 10.5,
            'gross_weight' => 25.8,
        ];

        $this->json($this->method, $this->endpoint, $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['employee_id', 'product_id', 'machine_id']);
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
    public function shouldValidateThatGrossWeightIsGreaterThanTareWieght()
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
     * Debe restringir acceso cuando el usuario no tiene permisos.
     *
     * @test
     */
    public function shouldReturnForbidenWhenUserDoesNotHavePermissions()
    {
        $this->user->permissions()->delete();

        $this->json($this->method, $this->endpoint, [])->assertForbidden();
    }
}
