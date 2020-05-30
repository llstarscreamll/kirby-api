<?php

namespace Novelties;

use Kirby\Employees\Models\Employee;
use Kirby\Novelties\Models\Novelty;

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
    public function searchByEmployeeId(ApiTester $I)
    {
        $employee = factory(Employee::class)->create();
        $expectedNovelties = factory(Novelty::class, 2)->create(['employee_id' => $employee->id]);
        factory(Novelty::class, 3)->create();

        $I->sendGET($this->endpoint, ['employee_id' => $employee->id]);

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
    public function shouldReturnUnprocesableEntityIfUserDoesntHaveRequiredPermissions(ApiTester $I)
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();
        factory(Novelty::class, 5)->create();

        $I->sendGET($this->endpoint);

        $I->seeResponseCodeIs(403);
    }
}
