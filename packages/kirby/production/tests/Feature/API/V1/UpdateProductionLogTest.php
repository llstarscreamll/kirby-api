<?php

namespace Kirby\Production\Tests\Feature\API\V1;

use Carbon\Carbon;
use Kirby\Customers\Models\Customer;
use Kirby\Employees\Models\Employee;
use Kirby\Machines\Models\Machine;
use Kirby\Production\Enums\Tag;
use Kirby\Production\Models\ProductionLog;
use Kirby\Products\Models\Product;
use Kirby\Users\Models\User;
use ProductionPackageSeed;
use Tests\TestCase;

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
        $this->actingAsAdmin($this->user = factory(User::class)->create());
    }

    /**
     * Debe persistir correctamente cuando los datos están correctos.
     *
     * @test
     */
    public function shouldBeUpdatedSuccessfullyWhenDataIsCorrect()
    {
        $log = factory(ProductionLog::class)->create();

        $payload = [
            'product_id' => $productId = factory(Product::class)->create()->id,
            'machine_id' => $machineId = factory(Machine::class)->create()->id,
            'employee_id' => $employeeId = factory(Employee::class)->create()->id,
            'customer_id' => $customerId = factory(Customer::class)->create()->id,
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
            'employee_id' => $employeeId,
            'machine_id' => $machineId,
            'customer_id' => $customerId,
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
        $log = factory(ProductionLog::class)->create(['tag_updated_at' => '2002-02-02 02:02:02']);

        $payload = [
            'employee_id' => $log->employee_id,
            'machine_id' => $log->machine_id,
            'product_id' => $log->product_id,
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
            'employee_id' => $log->employee_id,
            'machine_id' => $log->machine_id,
            'product_id' => $log->product_id,
        ];

        $this->json($this->method, str_replace('id', $log->id, $this->endpoint), $payload)
            ->assertOk()
            ->assertJsonPath('data', 'ok');

        $this->assertDatabaseHas('production_logs', [
            'id' => $log->id,
            'tag' => $log->tag,
            'tag_updated_at' => '2001-01-01 01:01:01'
        ]);
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
