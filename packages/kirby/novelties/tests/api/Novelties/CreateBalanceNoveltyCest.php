<?php

namespace Kirby\Novelties\Tests;

use Kirby\Employees\Models\Employee;
use Kirby\Novelties\Models\NoveltyType;
use NoveltiesPackageSeed;

/**
 * Class CreateBalanceNoveltyCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateBalanceNoveltyCest
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/novelties/balance';

    /**
     * @var \Kirby\Users\Models\User
     */
    private $user;

    /**
     * @param ApiTester $I
     */
    public function _before(ApiTester $I)
    {
        $I->callArtisan('db:seed', ['--class' => NoveltiesPackageSeed::class]);

        $this->user = $I->amLoggedAsAdminUser();
        $I->haveHttpHeader('Accept', 'application/json');
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldCreateAdditionBalanceNoveltySuccessfully(ApiTester $I)
    {
        $payload = [
            'employee_id' => ($employee = factory(Employee::class)->create())->id,
            'start_date' => '2020-01-01T05:00:00.000Z',
            'time' => '-5', // negative time should write addition novelty
            'comment' => 'test comment',
        ];

        $I->sendPOST($this->endpoint, $payload);

        $I->seeResponseCodeIs(201);
        $I->seeRecord('novelties', [
            'employee_id' => $employee->id,
            'novelty_type_id' => NoveltyType::whereCode('B+')->first()->id, // default novelty for addition
            'start_at' => '2020-01-01 05:00:00',
            'end_at' => '2020-01-01 10:00:00', // 5 hours
            'comment' => 'test comment',
        ]);
    }

    /**
     * @test
     * @param ApiTester $I
     */
    public function shouldCreateSubtractBalanceNoveltySuccessfully(ApiTester $I)
    {
        $payload = [
            'employee_id' => ($employee = factory(Employee::class)->create())->id,
            'start_date' => now()->setTimezone('America/Bogota')->setDateTime(2020, 01, 01, 00, 00, 00)->toISOString(),
            'time' => '5', // positive time should write subtract novelty
            'comment' => 'test comment',
        ];

        $I->sendPOST($this->endpoint, $payload);

        $I->seeResponseCodeIs(201);
        $I->seeRecord('novelties', [
            'employee_id' => $employee->id,
            'novelty_type_id' => NoveltyType::whereCode('B-')->first()->id, // default novelty for subtract
            'start_at' => '2020-01-01 05:00:00',
            'end_at' => '2020-01-01 10:00:00', // 5 hours
            'comment' => 'test comment',
        ]);
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
