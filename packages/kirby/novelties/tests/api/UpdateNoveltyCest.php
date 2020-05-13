<?php

namespace Novelties;

use Kirby\Employees\Models\Employee;
use Kirby\Novelties\Enums\NoveltyTypeOperator;
use Kirby\Novelties\Models\Novelty;
use Kirby\Novelties\Models\NoveltyType;

/**
 * Class UpdateNoveltyCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class UpdateNoveltyCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/novelties/{id}';

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
    public function updateNoveltySuccessfully(ApiTester $I)
    {
        $novelty = factory(Novelty::class)->create();

        $startDate = now()->addDay();
        $endDate = now()->addDay()->addHours(2);

        $updatedNovelty = [
            'employee_id' => factory(Employee::class)->create()->id,
            'novelty_type_id' => factory(NoveltyType::class)->create(['operator' => NoveltyTypeOperator::Subtraction])->id,
            'start_at' => $startDate->toISOString(),
            'end_at' => $endDate->toISOString(),
            'comment' => 'updated comment here!!',
        ];

        $endpoint = str_replace('{id}', $novelty->id, $this->endpoint);
        $I->sendPUT($endpoint, $updatedNovelty);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');

        $I->seeRecord('novelties', [
            'id' => $novelty->id,
            'start_at' => $startDate->toDateTimeString(),
            'end_at' => $endDate->toDateTimeString(),
        ] + $updatedNovelty);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldSetNegativeTimeIfNoveltyTypeHasSubtractorOperator(ApiTester $I)
    {
        $novelty = factory(Novelty::class)->create();

        $updatedNovelty = [
            'employee_id' => factory(Employee::class)->create()->id,
            'novelty_type_id' => factory(NoveltyType::class)->create(['operator' => NoveltyTypeOperator::Subtraction])->id,
        ];

        $endpoint = str_replace('{id}', $novelty->id, $this->endpoint);
        $I->sendPUT($endpoint, $updatedNovelty);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');

        $I->seeRecord('novelties', ['id' => $novelty->id] + $updatedNovelty);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldSetPositiveTimeIfNoveltyTypeHasAditionOperator(ApiTester $I)
    {
        $novelty = factory(Novelty::class)->create();

        $updatedNovelty = [
            'employee_id' => factory(Employee::class)->create()->id,
            'novelty_type_id' => factory(NoveltyType::class)->create(['operator' => NoveltyTypeOperator::Addition])->id,
        ];

        $endpoint = str_replace('{id}', $novelty->id, $this->endpoint);
        $I->sendPUT($endpoint, $updatedNovelty);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');

        $I->seeRecord('novelties', ['id' => $novelty->id] + $updatedNovelty);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnUnprocesableEntityIfUserDoesntHaveRequiredPermissions(ApiTester $I)
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();
        $novelty = factory(Novelty::class)->create();
        $endpoint = str_replace('{id}', $novelty->id, $this->endpoint);

        $I->sendPUT($endpoint, []);

        $I->seeResponseCodeIs(403);
    }
}
