<?php

namespace Kirby\Novelties\Tests;

use Kirby\Employees\Models\Employee;
use Kirby\Novelties\Actions\CreateManyNoveltiesAction;
use Kirby\Novelties\Models\NoveltyType;
use NoveltiesPackageSeed;

/**
 * Class CreateManyNoveltiesTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateManyNoveltiesTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/novelties/create-many';

    /**
     * @var \Kirby\Users\Models\User
     */
    private $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed(NoveltiesPackageSeed::class);
    }

    /**
     * @test
     */
    public function createNoveltiesToUsersSuccessfully()
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

        $user = factory(\Kirby\Users\Models\User::class)->create();

        $this->mock(CreateManyNoveltiesAction::class)
            ->shouldReceive('run')
            ->with($payload + ['approvers' => [$user->id]])
            ->andReturn(true);

        $this->actingAsAdmin($user)
            ->json('POST', $this->endpoint, $payload)->assertCreated();
    }

    /**
     * @test
     */
    public function shouldReturnForbidenWhenUserDoesntHaveRequiredPermissions()
    {
        $this->actingAsGuest()
            ->json('POST', $this->endpoint, [])
            ->assertForbidden();
    }
}
