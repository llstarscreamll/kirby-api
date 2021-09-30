<?php

namespace Kirby\Production\Tests\Feature\API\V1;

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
            'tag' => Tag::Rejected,
            'batch' => 1111,
            // los siguientes valores deben ser omitidos
            'customer_id' => 99999,
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
            'tag' => Tag::Rejected,
            'batch' => 1111,
            // los siguientes valores no deben ser actualizados
            'customer_id' => $log->customer_id,
            'tare_weight' => $log->tare_weight,
            'gross_weight' => $log->gross_weight,
        ]);
    }

    /**
     * Debe restringir acceso cuando el usuario no tiene permisos.
     *
     * @test
     */
    public function shouldReturnForbidenWhenUserDoesNotHavePermissions()
    {
        $log = factory(ProductionLog::class)->create();
        $this->user->permissions()->delete();

        $this->json($this->method, str_replace('id', $log->id, $this->endpoint), [])->assertForbidden();
    }
}
