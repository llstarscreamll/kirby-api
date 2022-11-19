<?php

namespace Kirby\Production\Tests\Feature\API\V1;

use Kirby\Customers\Models\Customer;
use Kirby\Employees\Models\Employee;
use Kirby\Employees\Models\Identification;
use Kirby\Machines\Models\Machine;
use Kirby\Production\Enums\Purpose;
use Kirby\Production\Enums\Tag;
use Kirby\Production\Models\ProductionLog;
use Kirby\Products\Models\Product;
use Kirby\Users\Models\User;
use ProductionPackageSeed;
use Tests\TestCase;

/**
 * @internal
 */
class updateProductionLogTest extends TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/production-logs/id';

    /**
     * @var string
     */
    private $method = 'PUT';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ProductionPackageSeed::class);
        $this->actingAsAdmin($this->user = factory(Employee::class)->create()->user);
    }

    /**
     * Debe persistir correctamente cuando los datos están correctos y se otorga
     * un código/token de empleado.
     *
     * @test
     */
    public function shouldBeUpdatedSuccessfullyWhenDataIsCorrectAndEmployeeCodeIsGiven()
    {
        $log = factory(ProductionLog::class)->create();

        $payload = [
            'product_id' => $productId = factory(Product::class)->create()->id,
            'machine_id' => $machineId = factory(Machine::class)->create()->id,
            'employee_code' => ($identification = factory(Identification::class)->create(['type' => 'uuid']))->code, // another employee
            'customer_id' => $customerId = factory(Customer::class)->create()->id,
            'purpose' => Purpose::Sales,
            'tag' => Tag::Rejected,
            'batch' => 1111,
            'tare_weight' => 1234,
            'gross_weight' => 5678,
        ];

        $this->json($this->method, str_replace('id', $log->id, $this->endpoint), $payload)
            ->assertOk()
            ->assertJsonPath('data', 'ok');

        $this->assertDatabaseHas('production_logs', [
            'product_id' => $productId,
            'employee_id' => $identification->employee_id,
            'machine_id' => $machineId,
            'customer_id' => $customerId,
            'purpose' => Purpose::Sales,
            'tag' => Tag::Rejected,
            'batch' => 1111,
            'tare_weight' => 1234,
            'gross_weight' => 5678,
        ]);
    }

    /**
     * Debe persistir correctamente cuando los datos están correctos y no se
     * otorga un código/token de empleado.
     *
     * @test
     */
    public function shouldBeUpdatedSuccessfullyWhenDataIsCorrectAndEmployeeCodeIsMissing()
    {
        // remove permission to create production logs on behalf of another person
        $this->user->revokePermissionTo('production-logs.create-on-behalf-of-another-person');
        $log = factory(ProductionLog::class)->create();

        $payload = [
            'product_id' => $productId = factory(Product::class)->create()->id,
            'machine_id' => $machineId = factory(Machine::class)->create()->id,
            'employee_code' => '', // no employee code
            'customer_id' => $customerId = factory(Customer::class)->create()->id,
            'purpose' => Purpose::Sales,
            'tag' => Tag::Rejected,
            'batch' => 1111,
            'tare_weight' => 1234,
            'gross_weight' => 5678,
        ];

        $this->json($this->method, str_replace('id', $log->id, $this->endpoint), $payload)
            ->assertOk()
            ->assertJsonPath('data', 'ok');

        $this->assertDatabaseHas('production_logs', [
            'product_id' => $productId,
            'employee_id' => $this->user->id, // row is now owned by authenticated user
            'machine_id' => $machineId,
            'customer_id' => $customerId,
            'purpose' => Purpose::Sales,
            'tag' => Tag::Rejected,
            'batch' => 1111,
            'tare_weight' => 1234,
            'gross_weight' => 5678,
        ]);
    }

    /**
     * Debe actualizar la fecha de edición de la etiqueta cuando el valor de la
     * etiqueta haya cambiado.
     *
     * @test
     */
    public function shouldUpdateTagUpdatedAtAttributeWhenTagIsChanged()
    {
        $log = factory(ProductionLog::class)->create(['tag' => Tag::InLine, 'tag_updated_at' => '2002-02-02 02:02:02']);

        $payload = [
            'machine_id' => $log->machine_id,
            'product_id' => $log->product_id,
            'employee_code' => ($identification = factory(Identification::class)->create(['type' => 'uuid']))->code, // another employee
            'purpose' => Purpose::Sales,
            'tag' => Tag::Rejected,
        ];

        $this->json($this->method, str_replace('id', $log->id, $this->endpoint), $payload)
            ->assertOk()
            ->assertJsonPath('data', 'ok');

        $this->assertDatabaseHas('production_logs', [
            'id' => $log->id,
            'tag' => Tag::Rejected,
            'tag_updated_at' => now()->toDateTimeString(),
        ]);
    }

    /**
     * No debe actualizar la fecha de edición de la etiqueta cuando el valor de
     * la etiqueta no ha cambiado.
     *
     * @test
     */
    public function shouldNotUpdateTagUpdatedAtAttributeWhenTagIsNotChanged()
    {
        $log = factory(ProductionLog::class)->create(['tag_updated_at' => '2001-01-01 01:01:01']);

        $payload = [
            'machine_id' => $log->machine_id,
            'product_id' => $log->product_id,
            'employee_code' => ($identification = factory(Identification::class)->create(['type' => 'uuid']))->code, // another employee
            'purpose' => Purpose::Sales,
            'tag' => $log->tag->value,
        ];

        $this->json($this->method, str_replace('id', $log->id, $this->endpoint), $payload)
            ->assertOk()
            ->assertJsonPath('data', 'ok');

        $this->assertDatabaseHas('production_logs', [
            'id' => $log->id,
            'tag' => $log->tag,
            'tag_updated_at' => '2001-01-01 01:01:01',
        ]);
    }

    /**
     * Debe validar que los códigos/IDs de las entidades existan.
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
     * Debe restringir acceso cuando el usuario no tiene permisos.
     *
     * @test
     */
    public function shouldReturnForbiddenWhenUserDoesNotHavePermissions()
    {
        $log = factory(ProductionLog::class)->create();
        $this->user->permissions()->delete();

        $this->json($this->method, str_replace('id', $log->id, $this->endpoint), [])->assertForbidden();
    }
}
