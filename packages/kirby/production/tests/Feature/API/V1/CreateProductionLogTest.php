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
     * @test
     */
    public function shouldBeCreatedSuccessfullyWhenBatchandCustomerIsMissing()
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
     * @test
     */
    public function shouldReturnUnprocessableEntityWhenProductOrMachineDoesNotExists()
    {
        $payload = [
            'product_id' => 999,
            'machine_id' => 999,
            'tare_weight' => 10.5,
            'gross_weight' => 25.8,
        ];

        $this->json($this->method, $this->endpoint, $payload)
            ->assertJsonValidationErrors(['product_id', 'machine_id']);
    }

    /**
     * @test
     */
    public function shouldReturnUnprocessableEntityWhenWeightAreNotNumeric()
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
     * @test
     */
    public function shouldReturnForbidenWhenUserDoesNotHavePermissions()
    {
        $this->user->permissions()->delete();

        $this->json($this->method, $this->endpoint, [])->assertForbidden();
    }
}
