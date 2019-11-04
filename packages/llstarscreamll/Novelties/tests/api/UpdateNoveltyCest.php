<?php

namespace Novelties;

use llstarscreamll\Employees\Models\Employee;
use llstarscreamll\Novelties\Models\Novelty;
use llstarscreamll\Novelties\Models\NoveltyType;

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
    public function getNoveltySuccessfully(ApiTester $I)
    {
        $novelty = factory(Novelty::class)->create();
        $endpoint = str_replace('{id}', $novelty->id, $this->endpoint);
        $updatedNovelty = [
            'employee_id' => factory(Employee::class)->create()->id,
            'novelty_type_id' => factory(NoveltyType::class)->create()->id,
            'total_time_in_minutes' => 12345,
        ];

        $I->sendPUT($endpoint, $updatedNovelty);

        $I->seeResponseCodeIs(200);
        $I->seeResponseJsonMatchesJsonPath('$.data.id');
        $I->seeResponseJsonMatchesJsonPath('$.data.time_clock_log_id');
        $I->seeResponseJsonMatchesJsonPath('$.data.employee_id');
        $I->seeResponseJsonMatchesJsonPath('$.data.novelty_type_id');
        $I->seeResponseJsonMatchesJsonPath('$.data.total_time_in_minutes');

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
