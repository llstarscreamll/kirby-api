<?php

namespace Kirby\Novelties\Tests\api\Novelties;

use Illuminate\Support\Facades\DB;
use Kirby\Employees\Models\Employee;
use Kirby\Novelties\Models\Novelty;
use Kirby\Novelties\Models\NoveltyType;
use NoveltiesPackageSeed;

/**
 * Class GetNoveltyTypesRecordsByEmployeeTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class GetNoveltiesGroupedByNoveltyTypeTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/novelties/resume-by-employee-and-novelty-types';

    /**
     * @var \Illuminate\Support\Collection<NoveltyType>
     */
    private $noveltyTypes;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed(NoveltiesPackageSeed::class);
        $this->noveltyTypes = NoveltyType::all();

        $this->actingAsAdmin($this->user = factory(\Kirby\Users\Models\User::class)->create());
    }

    /**
     * Debe devolver los datos de todos los empleados cuando tiene el permiso de
     * búsquedas globales de novedades, es decir, puede ver la info de todos los
     * empleados.
     *
     * @test
     */
    public function shouldReturnOkWithAllEmployeesDataWhenUserHasGlobalNoveltiesSearchPermission()
    {
        $tonyStart = factory(Employee::class)->create();
        $steveRogers = factory(Employee::class)->create();

        $tonyNovelties = factory(Novelty::class, 2)->create([
            'employee_id' => $tonyStart,
            'novelty_type_id' => $this->noveltyTypes->firstWhere('code', 'PP'),
            'start_at' => now()->startOfMonth()->addHours(2),
            'end_at' => now()->startOfMonth()->addHours(4),
        ]);

        $steveNovelties = factory(Novelty::class, 2)->create([
            'employee_id' => $steveRogers,
            'novelty_type_id' => $this->noveltyTypes->firstWhere('code', 'CM'),
            'start_at' => now()->startOfMonth()->addHours(4),
            'end_at' => now()->startOfMonth()->addHours(6),
        ]);

        // out of range novelty
        factory(Novelty::class)->create([
            'employee_id' => $steveRogers,
            'novelty_type_id' => $this->noveltyTypes->firstWhere('code', 'HN'),
            'start_at' => now()->subMonths(2)->addHours(4),
            'end_at' => now()->subMonths(2)->addHours(6),
        ]);

        // set config to return only PP, CM, HN novelties types
        DB::table('novelty_types')->update(['keep_in_report' => 0]);
        DB::table('novelty_types')->whereIn('code', ['PP', 'CM', 'HN'])->update(['keep_in_report' => 1]);

        $this->json('GET', $this->endpoint, [
            'start_date' => now()->startOfMonth()->toIsoString(),
            'end_date' => now()->endOfMonth()->toIsoString(),
        ])
            ->assertJsonPath('data.0.id', $tonyStart->id) // first employee data
            ->assertJsonPath('data.0.first_name', $tonyStart->first_name)
            ->assertJsonPath('data.0.last_name', $tonyStart->last_name)
            ->assertJsonPath('data.0.novelty_types.0.id', $tonyNovelties->first()->novelty_type_id)
            ->assertJsonPath('data.0.novelty_types.0.code', $tonyNovelties->first()->noveltyType->code)
            ->assertJsonPath('data.0.novelty_types.0.name', $tonyNovelties->first()->noveltyType->name)
            ->assertJsonPath('data.0.novelty_types.0.operator', $tonyNovelties->first()->noveltyType->operator->value)
            ->assertJsonPath('data.0.novelty_types.0.novelties.0.id', $tonyNovelties->first()->id)
            ->assertJsonPath('data.0.novelty_types.0.novelties.0.employee_id', $tonyNovelties->first()->employee_id)
            ->assertJsonPath('data.0.novelty_types.0.novelties.0.novelty_type_id', $tonyNovelties->first()->novelty_type_id)
            ->assertJsonPath('data.0.novelty_types.0.novelties.0.start_at', $tonyNovelties->first()->start_at->toIsoString())
            ->assertJsonPath('data.0.novelty_types.0.novelties.0.end_at', $tonyNovelties->first()->end_at->toIsoString())
            ->assertJsonPath('data.0.novelty_types.0.novelties.1.id', $tonyNovelties->last()->id)
            ->assertJsonPath('data.0.novelty_types.0.novelties.1.employee_id', $tonyNovelties->last()->employee_id)
            ->assertJsonPath('data.0.novelty_types.0.novelties.1.novelty_type_id', $tonyNovelties->last()->novelty_type_id)
            ->assertJsonPath('data.0.novelty_types.0.novelties.1.start_at', $tonyNovelties->last()->start_at->toIsoString())
            ->assertJsonPath('data.0.novelty_types.0.novelties.1.end_at', $tonyNovelties->last()->end_at->toIsoString())
            ->assertJsonPath('data.1.id', $steveRogers->id) // last employee data
            ->assertJsonPath('data.1.first_name', $steveRogers->first_name)
            ->assertJsonPath('data.1.last_name', $steveRogers->last_name)
            ->assertJsonPath('data.1.novelty_types.1.id', $steveNovelties->first()->novelty_type_id)
            ->assertJsonPath('data.1.novelty_types.1.code', $steveNovelties->first()->noveltyType->code)
            ->assertJsonPath('data.1.novelty_types.1.name', $steveNovelties->first()->noveltyType->name)
            ->assertJsonPath('data.1.novelty_types.1.operator', $steveNovelties->first()->noveltyType->operator->value)
            ->assertJsonPath('data.1.novelty_types.1.novelties.0.id', $steveNovelties->first()->id)
            ->assertJsonPath('data.1.novelty_types.1.novelties.0.employee_id', $steveNovelties->first()->employee_id)
            ->assertJsonPath('data.1.novelty_types.1.novelties.0.novelty_type_id', $steveNovelties->first()->novelty_type_id)
            ->assertJsonPath('data.1.novelty_types.1.novelties.0.start_at', $steveNovelties->first()->start_at->toIsoString())
            ->assertJsonPath('data.1.novelty_types.1.novelties.0.end_at', $steveNovelties->first()->end_at->toIsoString())
            ->assertJsonPath('data.1.novelty_types.1.novelties.1.id', $steveNovelties->last()->id)
            ->assertJsonPath('data.1.novelty_types.1.novelties.1.employee_id', $steveNovelties->last()->employee_id)
            ->assertJsonPath('data.1.novelty_types.1.novelties.1.novelty_type_id', $steveNovelties->last()->novelty_type_id)
            ->assertJsonPath('data.1.novelty_types.1.novelties.1.start_at', $steveNovelties->last()->start_at->toIsoString())
            ->assertJsonPath('data.1.novelty_types.1.novelties.1.end_at', $steveNovelties->last()->end_at->toIsoString())
            ->assertJsonMissingPath('data.1.novelty_types.0.novelties.0')
            ->assertJsonMissingPath('data.1.novelty_types.2.novelties.0')
            // employees should have all novelty types even if there are not novelties records
            ->assertJsonPath('data.1.id', $steveRogers->id)
            ->assertJsonPath('data.1.first_name', $steveRogers->first_name)
            ->assertJsonPath('data.1.novelty_types.0.code', $this->noveltyTypes->firstWhere('code', 'PP')->code)
            ->assertJsonPath('data.1.novelty_types.1.code', $this->noveltyTypes->firstWhere('code', 'CM')->code)
            ->assertJsonPath('data.1.novelty_types.2.code', $this->noveltyTypes->firstWhere('code', 'HN')->code)
            ->assertJsonPath('data.0.id', $tonyStart->id)
            ->assertJsonPath('data.0.first_name', $tonyStart->first_name)
            ->assertJsonPath('data.0.novelty_types.0.code', $this->noveltyTypes->firstWhere('code', 'PP')->code)
            ->assertJsonPath('data.0.novelty_types.1.code', $this->noveltyTypes->firstWhere('code', 'CM')->code)
            ->assertJsonPath('data.0.novelty_types.2.code', $this->noveltyTypes->firstWhere('code', 'HN')->code);
    }

    /**
     * Debe devolver los datos del usuario autenticado nada más cuando no tiene
     * permiso para ver los datos de los demás empleados.
     *
     * @test
     */
    public function shouldReturnOkWithCurrentUserEmployeesDataWhenHasNotGlobalNoveltiesSearchPermission()
    {
        $tonyStart = factory(Employee::class)->create(['id' => $this->user->id]);
        $steveRogers = factory(Employee::class)->create();
        DB::table('permissions')->where('name', 'novelties.global-search')->delete();

        factory(Novelty::class, 2)->create([
            'employee_id' => $tonyStart,
            'novelty_type_id' => $this->noveltyTypes->firstWhere('code', 'PP'),
            'start_at' => now()->startOfMonth()->addHours(2),
            'end_at' => now()->startOfMonth()->addHours(4),
        ]);

        factory(Novelty::class, 2)->create([
            'employee_id' => $steveRogers,
            'novelty_type_id' => $this->noveltyTypes->firstWhere('code', 'CM'),
            'start_at' => now()->startOfMonth()->addHours(4),
            'end_at' => now()->startOfMonth()->addHours(6),
        ]);

        // set config to return only PP, CM, HN novelties types
        DB::table('novelty_types')->update(['keep_in_report' => 0]);
        DB::table('novelty_types')->whereIn('code', ['PP', 'CM', 'HN'])->update(['keep_in_report' => 1]);

        $this->json('GET', $this->endpoint, [
            'start_date' => now()->startOfMonth()->toIsoString(),
            'end_date' => now()->endOfMonth()->toIsoString(),
        ])
            ->assertJsonCount(1, 'data') // solo las novedades del usuario actual se deben mostrar
            ->assertJsonPath('data.0.id', $this->user->id);
    }

    /**
     * @test
     */
    public function shouldReturnUnprocesableEntityWhenStarAndEndDatesAreMissing()
    {
        $this->json('GET', $this->endpoint)
            ->assertStatus(422);
    }

    /**
     * @test
     */
    public function shouldReturnForbidenWhenUserDoesntHaveRequiredPermissions()
    {
        $this->actingAsGuest()
            ->json('GET', $this->endpoint)
            ->assertForbidden();
    }
}
