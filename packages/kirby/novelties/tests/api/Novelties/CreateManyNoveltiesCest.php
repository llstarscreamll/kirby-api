<?php

namespace Novelties;

use Kirby\Employees\Models\Employee;
use Kirby\Novelties\Actions\CreateManyNoveltiesAction;
use Kirby\Novelties\Models\NoveltyType;
use Mockery;

/**
 * Class CreateManyNoveltiesCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateManyNoveltiesCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/novelties/create-many';

    /**
     * @var \Kirby\Users\Models\User
     */
    private $user;

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
                    'start_at' => '2019-01-01T10:00:00.000Z',
                    'end_at' => '2019-01-01T12:00:00.000Z',
                    'comment' => '',
                ],
                [
                    'novelty_type_id' => factory(NoveltyType::class)->create()->id,
                    'start_at' => '2019-02-20T14:00:00.000Z',
                    'end_at' => '2019-02-20T16:00:00.000Z',
                    'comment' => 'test comment',
                ],
            ],
        ];

        $actionMock = Mockery::mock(CreateManyNoveltiesAction::class)
            ->shouldReceive('run')
            ->with($payload + ['approvers' => [$this->user->id]])
            ->andReturn(true)
            ->getMock();

        $I->haveInstance(CreateManyNoveltiesAction::class, $actionMock);

        $I->sendPOST($this->endpoint, $payload);

        $I->seeResponseCodeIs(201);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldReturnForbidenWhenUserDoesntHaveRequiredPermissions(ApiTester $I)
    {
        $this->user->roles()->delete();
        $this->user->permissions()->delete();

        $I->sendPOST($this->endpoint, []);

        $I->seeResponseCodeIs(403);
    }
}
