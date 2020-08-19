<?php

namespace Kirby\Novelties\Tests\Actions;

use Kirby\Employees\Models\Employee;
use Kirby\Novelties\Actions\CreateManyNoveltiesAction;
use Kirby\Novelties\Enums\NoveltyTypeOperator;
use Kirby\Novelties\Models\NoveltyType;
use Kirby\Users\Models\User;

/**
 * Class CreateManyNoveltiesActionTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateManyNoveltiesActionTest extends \Tests\TestCase
{

    public function setUp(): void
    {
        parent::setUp();
        factory(NoveltyType::class)->create(['operator' => NoveltyTypeOperator::Subtraction]);
        factory(NoveltyType::class, 4)->create();
    }

    public function successCases()
    {
        return [
            [
                'employees' => 5,
                'approvers' => 2,
                'novelties' => [
                    [
                        'novelty_type_id' => 1,
                        'start_at' => '2018-01-01T10:00:00.000Z',
                        'end_at' => '2018-01-01T12:00:00.000Z',
                        'comment' => '',
                    ],
                    [
                        'novelty_type_id' => 2,
                        'start_at' => '2018-02-20T14:00:00.000Z',
                        'end_at' => '2018-02-20T16:00:00.000Z',
                        'comment' => 'test comment',
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider successCases
     */
    public function testToRunAction($employees, $approvers, $novelties)
    {
        $employees = factory(Employee::class, $employees)->create();
        $approvers = factory(User::class, $approvers)->create();

        $action = app(CreateManyNoveltiesAction::class);
        $result = $action->run([
            'employee_ids' => $employees->pluck('id')->all(),
            'novelties' => $novelties,
            'approvers' => $approvers->pluck('id')->all(),
        ]);

        $this->assertTrue($result);

        // novelties should be created successfully
        $employees->each(function ($employee) use ($novelties) {
            foreach ($novelties as $novelty) {
                $this->assertDatabaseHas('novelties', [
                    'employee_id' => $employee->id,
                    'novelty_type_id' => $novelty['novelty_type_id'],
                    'start_at' => str_replace(['T', '.000Z'], [' ', ''], $novelty['start_at']),
                    'end_at' => str_replace(['T', '.000Z'], [' ', ''], $novelty['end_at']),
                ]);
            }
        });

        // novelty approvals
        $approvers->each(fn($approver) => $this->assertDatabaseRecordsCount(
            count($novelties) * $employees->count(), 'novelty_approvals', ['user_id' => $approver->id]
        ));
    }
}
