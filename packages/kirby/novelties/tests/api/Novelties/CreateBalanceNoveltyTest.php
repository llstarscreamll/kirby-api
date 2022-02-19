<?php

namespace Kirby\Novelties\Tests\api\Novelties;

use Kirby\Employees\Models\Employee;
use Kirby\Novelties\Models\NoveltyType;
use NoveltiesPackageSeed;

/**
 * Class CreateBalanceNoveltyTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 *
 * @internal
 */
class CreateBalanceNoveltyTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/novelties/balance';

    /**
     * @var \Kirby\Users\Models\User
     */
    private $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed(NoveltiesPackageSeed::class);

        $this->actingAsAdmin($this->user = factory(\Kirby\Users\Models\User::class)->create());
    }

    /**
     * @test
     */
    public function shouldCreateAdditionBalanceNoveltySuccessfully()
    {
        $payload = [
            'employee_id' => ($employee = factory(Employee::class)->create())->id,
            'start_date' => '2020-01-01T05:00:00.000Z',
            'time' => '-5', // negative time should write addition novelty
            'comment' => 'test comment',
        ];

        $this->json('POST', $this->endpoint, $payload)
            ->assertCreated();
        $this->assertDatabaseHas('novelties', [
            'employee_id' => $employee->id,
            'novelty_type_id' => NoveltyType::whereCode('B+')->first()->id, // default novelty for addition
            'start_at' => '2020-01-01 05:00:00',
            'end_at' => '2020-01-01 10:00:00', // 5 hours
            'comment' => 'test comment',
        ]);
    }

    /**
     * @test
     */
    public function shouldCreateSubtractBalanceNoveltySuccessfully()
    {
        $payload = [
            'employee_id' => ($employee = factory(Employee::class)->create())->id,
            'start_date' => now()->setTimezone('America/Bogota')->setDateTime(2020, 01, 01, 00, 00, 00)->toISOString(),
            'time' => '5', // positive time should write subtract novelty
            'comment' => 'test comment',
        ];

        $this->json('POST', $this->endpoint, $payload)
            ->assertCreated();
        $this->assertDatabaseHas('novelties', [
            'employee_id' => $employee->id,
            'novelty_type_id' => NoveltyType::whereCode('B-')->first()->id, // default novelty for subtract
            'start_at' => '2020-01-01 05:00:00',
            'end_at' => '2020-01-01 10:00:00', // 5 hours
            'comment' => 'test comment',
        ]);
    }

    /**
     * @test
     */
    public function shouldCreateBalanceNoveltySuccessfullyWithDecimalPressition()
    {
        $payload = [
            'employee_id' => ($employee = factory(Employee::class)->create())->id,
            'start_date' => now()->setTimezone('America/Bogota')->setDateTime(2020, 01, 01, 00, 00, 00)->toISOString(),
            'time' => '0.5', // decimal positive
            'comment' => 'test comment',
        ];

        $this->json('POST', $this->endpoint, $payload)
            ->assertCreated();

        $this->assertDatabaseHas('novelties', [
            'employee_id' => $employee->id,
            'novelty_type_id' => NoveltyType::whereCode('B-')->first()->id, // default novelty for subtract
            'start_at' => '2020-01-01 05:00:00',
            'end_at' => '2020-01-01 05:30:00', // 0.5 hours (30 minutes)
            'comment' => 'test comment',
        ]);
    }

    /**
     * @test
     */
    public function shouldReturnForbiddenWhenUserDoesntHaveRequiredPermissions()
    {
        $this->actingAsGuest()
            ->json('POST', $this->endpoint, [])
            ->assertForbidden();
    }
}
