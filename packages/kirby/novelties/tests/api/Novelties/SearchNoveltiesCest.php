<?php

namespace Novelties;

use Kirby\Company\Models\SubCostCenter;
use Kirby\Employees\Models\Employee;
use Kirby\Novelties\Models\Novelty;
use Kirby\Novelties\Models\NoveltyType;
use Kirby\TimeClock\Models\TimeClockLog;

/**
 * Class SearchNoveltiesCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SearchNoveltiesCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/novelties/';

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        $this->user = $I->amLoggedAsAdminUser();
        $I->haveHttpHeader('Accept', 'application/json');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function searchSuccessfullyWithoutAnyParams(ApiTester $I)
    {
        $novelties = factory(Novelty::class, 5)->create();

        $I->sendGET($this->endpoint);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.0.id');
        $I->seeResponseJsonMatchesJsonPath('$.data.1.id');
        $I->seeResponseJsonMatchesJsonPath('$.data.2.id');
        $I->seeResponseJsonMatchesJsonPath('$.data.3.id');
        $I->seeResponseJsonMatchesJsonPath('$.data.4.id');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function searchByDateRange(ApiTester $I)
    {
        $expectedNovelties = factory(Novelty::class, 2)->create([
            'start_at' => now()->subDays(2),
            'end_at' => now()->subDays(2)->addHours(2),
        ]);
        factory(Novelty::class, 3)->create([
            'start_at' => now()->subMonths(2),
            'end_at' => now()->subMonths(2)->addHours(2),
        ]);

        $I->sendGET($this->endpoint, [
            'start_at' => [
                'from' => now()->subWeek()->startOfDay()->toISOString(),
                'to' => now()->endOfDay()->toISOString(),
            ],
        ]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.0.id');
        $I->seeResponseJsonMatchesJsonPath('$.data.1.id');
        $I->dontSeeResponseJsonMatchesJsonPath('$.data.2.id');
        $I->dontSeeResponseJsonMatchesJsonPath('$.data.3.id');
        $I->dontSeeResponseJsonMatchesJsonPath('$.data.4.id');
        $I->seeResponseContainsJson(['id' => $expectedNovelties[0]->id]);
        $I->seeResponseContainsJson(['id' => $expectedNovelties[1]->id]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function searchByEmployees(ApiTester $I)
    {
        $employee = factory(Employee::class)->create();
        $expectedNovelties = factory(Novelty::class, 2)->create(['employee_id' => $employee->id]);
        factory(Novelty::class, 3)->create();

        $I->sendGET($this->endpoint, ['employees' => [['id' => $employee->id]]]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.0.id');
        $I->seeResponseJsonMatchesJsonPath('$.data.1.id');
        $I->dontSeeResponseJsonMatchesJsonPath('$.data.2.id');
        $I->dontSeeResponseJsonMatchesJsonPath('$.data.3.id');
        $I->dontSeeResponseJsonMatchesJsonPath('$.data.4.id');
        $I->seeResponseContainsJson(['id' => $expectedNovelties[0]->id]);
        $I->seeResponseContainsJson(['id' => $expectedNovelties[1]->id]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function searchByCostCenter(ApiTester $I)
    {
        $employee = factory(Employee::class)->create();
        $subCostCenter = factory(SubCostCenter::class)->create();
        $expectedNovelties = factory(Novelty::class, 2)->create([
            'employee_id' => $employee->id,
            'sub_cost_center_id' => $subCostCenter->id,
        ]);
        factory(Novelty::class, 3)->create();

        $I->sendGET($this->endpoint, ['cost_centers' => [
            ['id' => $subCostCenter->cost_center_id],
        ]]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.0.id');
        $I->seeResponseJsonMatchesJsonPath('$.data.1.id');
        $I->dontSeeResponseJsonMatchesJsonPath('$.data.2.id');
        $I->dontSeeResponseJsonMatchesJsonPath('$.data.3.id');
        $I->dontSeeResponseJsonMatchesJsonPath('$.data.4.id');
        $I->seeResponseContainsJson(['id' => $expectedNovelties[0]->id]);
        $I->seeResponseContainsJson(['id' => $expectedNovelties[1]->id]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function searchByTimeClockLogCheckOutDateRange(ApiTester $I)
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

        $I->sendGET($this->endpoint, [
            'time_clock_log_check_out_start_date' => now()->subWeek()->startOfDay()->toISOString(),
            'time_clock_log_check_out_end_date' => now()->endOfDay()->toISOString(),
        ]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.0.id');
        $I->seeResponseJsonMatchesJsonPath('$.data.1.id');
        $I->seeResponseJsonMatchesJsonPath('$.data.2.id');
        $I->dontSeeResponseJsonMatchesJsonPath('$.data.3.id');
        $I->dontSeeResponseJsonMatchesJsonPath('$.data.4.id');
        $I->dontSeeResponseJsonMatchesJsonPath('$.data.5.id');
        $I->seeResponseContainsJson(['id' => $expectedNovelties[0]->id]);
        $I->seeResponseContainsJson(['id' => $expectedNovelties[1]->id]);
        $I->seeResponseContainsJson(['id' => $expectedNovelties[2]->id]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function searchByNoveltyTypes(ApiTester $I)
    {
        factory(Novelty::class, 3)->create();
        $noveltyType = factory(NoveltyType::class)->create();
        $expectedNovelties = factory(Novelty::class, 2)->create(['novelty_type_id' => $noveltyType->id]);

        $I->sendGET($this->endpoint, [
            'novelty_types' => [['id' => $noveltyType->id]],
        ]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.0.id');
        $I->seeResponseJsonMatchesJsonPath('$.data.1.id');
        $I->dontSeeResponseJsonMatchesJsonPath('$.data.2.id');
        $I->dontSeeResponseJsonMatchesJsonPath('$.data.3.id');
        $I->dontSeeResponseJsonMatchesJsonPath('$.data.4.id');
        $I->seeResponseContainsJson(['id' => $expectedNovelties[0]->id]);
        $I->seeResponseContainsJson(['id' => $expectedNovelties[1]->id]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnForbidenWhenUserDoesntHaveRequiredPermissions(ApiTester $I)
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();
        factory(Novelty::class, 5)->create();

        $I->sendGET($this->endpoint);

        $I->seeResponseCodeIs(403);
    }
}
