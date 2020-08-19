<?php

namespace Kirby\Novelties\Tests\api\Novelties;

use Kirby\Company\Models\SubCostCenter;
use Kirby\Employees\Models\Employee;
use Kirby\Novelties\Models\Novelty;
use Kirby\Novelties\Models\NoveltyType;
use Kirby\TimeClock\Models\TimeClockLog;
use NoveltiesPackageSeed;

/**
 * Class SearchNoveltiesTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SearchNoveltiesTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/novelties/';

    public function setUp(): void
    {
        parent::setUp();

        $this->seed(NoveltiesPackageSeed::class);
        $this->actingAsAdmin($this->user = factory(\Kirby\Users\Models\User::class)->create());
    }

    /**
     * @test
     */
    public function searchSuccessfullyWithoutAnyParams()
    {
        $novelties = factory(Novelty::class, 5)->create();

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
    public function searchByDateRange()
    {
        $expectedNovelties = factory(Novelty::class, 2)->create([
            'start_at' => now()->subDays(2),
            'end_at' => now()->subDays(2)->addHours(2),
        ]);
        factory(Novelty::class, 3)->create([
            'start_at' => now()->subMonths(2),
            'end_at' => now()->subMonths(2)->addHours(2),
        ]);

        $this->json('GET', $this->endpoint, [
            'start_at' => [
                'from' => now()->subWeek()->startOfDay()->toIsoString(),
                'to' => now()->endOfDay()->toIsoString(),
            ],
        ])
            ->assertOk()
            ->assertJsonHasPath('data.0.id')
            ->assertJsonHasPath('data.1.id')
            ->assertJsonMissingPath('data.2.id')
            ->assertJsonMissingPath('data.3.id')
            ->assertJsonMissingPath('data.4.id')
            ->assertJsonFragment(['id' => $expectedNovelties[0]->id])
            ->assertJsonFragment(['id' => $expectedNovelties[1]->id]);
    }

    /**
     * @test
     */
    public function searchByEmployees()
    {
        $employee = factory(Employee::class)->create();
        $expectedNovelties = factory(Novelty::class, 2)->create(['employee_id' => $employee->id]);
        factory(Novelty::class, 3)->create();

        $this->json('GET', $this->endpoint, ['employees' => [['id' => $employee->id]]])
            ->assertOk()
            ->assertJsonHasPath('data.0.id')
            ->assertJsonHasPath('data.1.id')
            ->assertJsonMissingPath('data.2.id')
            ->assertJsonMissingPath('data.3.id')
            ->assertJsonMissingPath('data.4.id')
            ->assertJsonFragment(['id' => $expectedNovelties[0]->id])
            ->assertJsonFragment(['id' => $expectedNovelties[1]->id]);
    }

    /**
     * @test
     */
    public function searchByCostCenter()
    {
        $employee = factory(Employee::class)->create();
        $subCostCenter = factory(SubCostCenter::class)->create();
        $expectedNovelties = factory(Novelty::class, 2)->create([
            'employee_id' => $employee->id,
            'sub_cost_center_id' => $subCostCenter->id,
        ]);
        factory(Novelty::class, 3)->create();

        $this->json('GET', $this->endpoint, ['cost_centers' => [
            ['id' => $subCostCenter->cost_center_id],
        ]])
            ->assertOk()
            ->assertJsonHasPath('data.0.id')
            ->assertJsonHasPath('data.1.id')
            ->assertJsonMissingPath('data.2.id')
            ->assertJsonMissingPath('data.3.id')
            ->assertJsonMissingPath('data.4.id')
            ->assertJsonFragment(['id' => $expectedNovelties[0]->id])
            ->assertJsonFragment(['id' => $expectedNovelties[1]->id]);
    }

    /**
     * @test
     */
    public function searchByTimeClockLogCheckOutDateRange()
    {
        $timeClockLog = factory(TimeClockLog::class)->create([
            'checked_in_at' => now()->subWeek(),
            'checked_out_at' => now()->subWeek()->addHours(9),
        ]);
        $expectedNovelties = factory(Novelty::class, 2)->create(['time_clock_log_id' => $timeClockLog->id]);
        $expectedNovelties = $expectedNovelties->push(factory(Novelty::class)->create([ // novelty without related time lock log
            'start_at' => now()->subWeek(),
            'end_at' => now()->subWeek()->addHours(9),
        ]));
        factory(Novelty::class, 3)->create();

        $this->json('GET', $this->endpoint, [
            'time_clock_log_check_out_start_date' => now()->subWeek()->startOfDay()->toIsoString(),
            'time_clock_log_check_out_end_date' => now()->endOfDay()->toIsoString(),
        ])
            ->assertOk()
            ->assertJsonHasPath('data.0.id')
            ->assertJsonHasPath('data.1.id')
            ->assertJsonHasPath('data.2.id')
            ->assertJsonMissingPath('data.3.id')
            ->assertJsonMissingPath('data.4.id')
            ->assertJsonMissingPath('data.5.id')
            ->assertJsonFragment(['id' => $expectedNovelties[0]->id])
            ->assertJsonFragment(['id' => $expectedNovelties[1]->id])
            ->assertJsonFragment(['id' => $expectedNovelties[2]->id]);
    }

    /**
     * @test
     */
    public function searchByNoveltyTypes()
    {
        factory(Novelty::class, 3)->create();
        $noveltyType = factory(NoveltyType::class)->create();
        $expectedNovelties = factory(Novelty::class, 2)->create(['novelty_type_id' => $noveltyType->id]);

        $this->json('GET', $this->endpoint, [
            'novelty_types' => [['id' => $noveltyType->id]],
        ])
            ->assertOk()
            ->assertJsonHasPath('data.0.id')
            ->assertJsonHasPath('data.1.id')
            ->assertJsonMissingPath('data.2.id')
            ->assertJsonMissingPath('data.3.id')
            ->assertJsonMissingPath('data.4.id')
            ->assertJsonFragment(['id' => $expectedNovelties[0]->id])
            ->assertJsonFragment(['id' => $expectedNovelties[1]->id]);
    }

    /**
     * @test
     */
    public function shouldReturnForbidenWhenUserDoesntHaveRequiredPermissions()
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();
        factory(Novelty::class, 5)->create();

        $this->json('GET', $this->endpoint)
            ->assertForbidden();
    }
}
