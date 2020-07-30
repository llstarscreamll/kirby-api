<?php

namespace Novelties\Actions;

use Codeception\Example;
use Kirby\Employees\Models\Employee;
use Kirby\Novelties\Actions\CreateManyNoveltiesAction;
use Kirby\Novelties\Enums\NoveltyTypeOperator;
use Kirby\Novelties\Models\NoveltyType;
use Kirby\Users\Models\User;
use Mockery;
use Novelties\IntegrationTester;

/**
 * Class CreateManyNoveltiesActionCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateManyNoveltiesActionCest
{
    /**
     * @param IntegrationTester $I
     */
    public function _before(IntegrationTester $I)
    {
        factory(NoveltyType::class)->create(['operator' => NoveltyTypeOperator::Subtraction]);
        factory(NoveltyType::class, 4)->create();
    }

    /**
     * @param IntegrationTester $I
     */
    public function _after(IntegrationTester $I)
    {
        Mockery::close();
    }

    /**
     * @test
     */
    protected function successCases()
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
     * @param IntegrationTester $I
     */
    public function testToRunAction(IntegrationTester $I, Example $data)
    {
        $employees = factory(Employee::class, $data['employees'])->create();
        $approvers = factory(User::class, $data['approvers'])->create();

        $action = app(CreateManyNoveltiesAction::class);
        $result = $action->run([
            'employee_ids' => $employees->pluck('id')->all(),
            'novelties' => $data['novelties'],
            'approvers' => $approvers->pluck('id')->all(),
        ]);

        $I->assertTrue($result);

        // novelties should be created successfully
        $employees->each(function ($employee) use ($I, $data) {
            foreach ($data['novelties'] as $novelty) {
                $I->seeRecord('novelties', [
                    'employee_id' => $employee->id,
                    'novelty_type_id' => $novelty['novelty_type_id'],
                    'start_at' => str_replace(['T', '.000Z'], [' ', ''], $novelty['start_at']),
                    'end_at' => str_replace(['T', '.000Z'], [' ', ''], $novelty['end_at']),
                ]);
            }
        });

        // novelty approvals
        $approvers->each(fn ($approver) => $I->seeNumRecords(
            count($data['novelties']) * $data['employees'], 'novelty_approvals', ['user_id' => $approver->id]
        ));
    }
}
