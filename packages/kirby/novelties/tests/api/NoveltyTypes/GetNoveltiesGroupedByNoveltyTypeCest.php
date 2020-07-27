<?php

namespace Novelties;

use DefaultNoveltyTypesSeed;
use Illuminate\Support\Facades\DB;
use Kirby\Employees\Models\Employee;
use Kirby\Novelties\Models\Novelty;
use Kirby\Novelties\Models\NoveltyType;

/**
 * Class GetNoveltyTypesRecordsByEmployeeCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class GetNoveltiesGroupedByNoveltyTypeCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/novelties/resume-by-employee-and-novelty-types';

    /**
     * @var \Illuminate\Support\Collection<NoveltyType>
     */
    private $noveltyTypes;

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        $I->callArtisan('db:seed', ['--class' => DefaultNoveltyTypesSeed::class]);
        $this->noveltyTypes = NoveltyType::all();

        $this->user = $I->amLoggedAsAdminUser();
        $I->haveHttpHeader('Accept', 'application/json');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnOkWithCurrentMonthDataWhenThereAreExistingRecords(ApiTester $I)
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
        $outOfRangeNovelty = factory(Novelty::class)->create([
            'employee_id' => $steveRogers,
            'novelty_type_id' => $this->noveltyTypes->firstWhere('code', 'HN'),
            'start_at' => now()->subMonths(2)->addHours(4),
            'end_at' => now()->subMonths(2)->addHours(6),
        ]);

        // set config to return only PP, CM, HN novelties types
        DB::table('novelty_types')->update(['keep_in_report' => 0]);
        DB::table('novelty_types')->whereIn('code', ['PP', 'CM', 'HN'])->update(['keep_in_report' => 1]);

        $I->sendGET($this->endpoint, [
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
        ]);

        // first employee data
        $I->seeResponseContainsJson([
            'id' => $tonyStart->id,
            'first_name' => $tonyStart->first_name,
            'last_name' => $tonyStart->last_name,
            'novelty_types' => [
                [
                    'id' => $tonyNovelties->first()->novelty_type_id,
                    'code' => $tonyNovelties->first()->noveltyType->code,
                    'name' => $tonyNovelties->first()->noveltyType->name,
                    'operator' => $tonyNovelties->first()->noveltyType->operator->value,
                    'novelties' => [
                        [
                            'id' => $tonyNovelties->first()->id,
                            'employee_id' => $tonyNovelties->first()->employee_id,
                            'novelty_type_id' => $tonyNovelties->first()->novelty_type_id,
                            'start_at' => $tonyNovelties->first()->start_at->toIsoString(),
                            'end_at' => $tonyNovelties->first()->end_at->toIsoString(),
                        ],
                        [
                            'id' => $tonyNovelties->last()->id,
                            'employee_id' => $tonyNovelties->last()->employee_id,
                            'novelty_type_id' => $tonyNovelties->last()->novelty_type_id,
                            'start_at' => $tonyNovelties->last()->start_at->toIsoString(),
                            'end_at' => $tonyNovelties->last()->end_at->toIsoString(),
                        ],
                    ],
                ],
            ],
        ], 'in range novelties from first employee are returned');

        // last employee data
        $I->seeResponseContainsJson([
            'id' => $steveRogers->id,
            'first_name' => $steveRogers->first_name,
            'last_name' => $steveRogers->last_name,
            'novelty_types' => [
                [
                    'id' => $steveNovelties->first()->novelty_type_id,
                    'code' => $steveNovelties->first()->noveltyType->code,
                    'name' => $steveNovelties->first()->noveltyType->name,
                    'operator' => $steveNovelties->first()->noveltyType->operator->value,
                    'novelties' => [
                        [
                            'id' => $steveNovelties->first()->id,
                            'employee_id' => $steveNovelties->first()->employee_id,
                            'novelty_type_id' => $steveNovelties->first()->novelty_type_id,
                            'start_at' => $steveNovelties->first()->start_at->toIsoString(),
                            'end_at' => $steveNovelties->first()->end_at->toIsoString(),
                        ],
                        [
                            'id' => $steveNovelties->last()->id,
                            'employee_id' => $steveNovelties->last()->employee_id,
                            'novelty_type_id' => $steveNovelties->last()->novelty_type_id,
                            'start_at' => $steveNovelties->last()->start_at->toIsoString(),
                            'end_at' => $steveNovelties->last()->end_at->toIsoString(),
                        ],
                    ],
                ],
            ],
        ], 'in range novelties from second employee are returned');

        $I->dontSeeResponseContainsJson(['novelties' => [
            [
                'id' => $outOfRangeNovelty->id,
                'employee_id' => $outOfRangeNovelty->employee_id,
                'novelty_type_id' => $outOfRangeNovelty->novelty_type_id,
                'start_at' => $outOfRangeNovelty->start_at->toIsoString(),
                'end_at' => $outOfRangeNovelty->end_at->toIsoString(),
            ],
        ],
        ], 'out of date range novelties should not be returned');

        // employees should have all novelty types even if there are not novelties records
        $I->seeResponseContainsJson([
            'id' => $steveRogers->id,
            'first_name' => $steveRogers->first_name,
            'novelty_types' => [
                ['code' => $this->noveltyTypes->firstWhere('code', 'CM')->code],
                ['code' => $this->noveltyTypes->firstWhere('code', 'HN')->code],
                ['code' => $this->noveltyTypes->firstWhere('code', 'PP')->code],
            ],
        ], 'first employee has all novelty types');

        $I->seeResponseContainsJson([
            'id' => $tonyStart->id,
            'first_name' => $tonyStart->first_name,
            'novelty_types' => [
                ['code' => $this->noveltyTypes->firstWhere('code', 'CM')->code],
                ['code' => $this->noveltyTypes->firstWhere('code', 'HN')->code],
                ['code' => $this->noveltyTypes->firstWhere('code', 'PP')->code],
            ],
        ], 'second employee has all novelty types');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnUnprocesableEntityWhenStarAndEndDatesAreMissing(ApiTester $I)
    {
        $I->sendGET($this->endpoint);

        $I->seeResponseCodeIs(422);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnForbidenWhenUserDoesntHaveRequiredPermissions(ApiTester $I)
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();

        $I->sendGET($this->endpoint);

        $I->seeResponseCodeIs(403);
    }
}
