<?php

namespace Novelties;

use Kirby\Employees\Models\Employee;
use Kirby\Novelties\Models\NoveltyType;

/**
 * Class CreateNoveltiesToUsersCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateNoveltiesToUsersCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/novelties/create-novelties-to-users';

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
    public function createNoveltiesToUsersSuccessfully(ApiTester $I)
    {
        $payload = [
            'employee_ids' => factory(Employee::class, 3)->create()->pluck('id')->all(),
            'novelties' => [
                [
                    'novelty_type_id' => factory(NoveltyType::class)->create()->id,
                    'scheduled_start_at' => '2019-01-01T10:00:00.000Z',
                    'scheduled_end_at' => '2019-01-01T12:00:00.000Z',
                    'comment' => '',
                ],
                [
                    'novelty_type_id' => factory(NoveltyType::class)->create()->id,
                    'scheduled_start_at' => '2019-02-20T14:00:00.000Z',
                    'scheduled_end_at' => '2019-02-20T16:00:00.000Z',
                    'comment' => 'test comment',
                ],
            ],
        ];

        $I->sendPOST($this->endpoint, $payload);

        $I->seeResponseCodeIs(201);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnUnprocesableEntityIfUserDoesntHaveRequiredPermissions(ApiTester $I)
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();

        $I->sendPOST($this->endpoint, []);

        $I->seeResponseCodeIs(403);
    }
}