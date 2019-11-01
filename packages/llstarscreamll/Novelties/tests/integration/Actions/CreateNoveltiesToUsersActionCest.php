<?php

namespace Novelties\Actions;

use Mockery;
use Codeception\Example;
use Novelties\IntegrationTester;
use llstarscreamll\Employees\Models\Employee;
use llstarscreamll\Novelties\Models\NoveltyType;
use llstarscreamll\Novelties\Enums\NoveltyTypeOperator;
use llstarscreamll\Novelties\Actions\CreateNoveltiesToUsersAction;

/**
 * Class CreateNoveltiesToUsersActionCest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateNoveltiesToUsersActionCest
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
                'employees_to_create' => 5,
                'novelties' => [
                    [
                        'novelty_type_id' => 1,
                        'scheduled_start_at' => '2018-01-01 10:00:00',
                        'scheduled_end_at' => '2018-01-01 12:00:00',
                        'total_time_in_minutes' => -120,
                        'comment' => '',
                    ],
                    [
                        'novelty_type_id' => 2,
                        'scheduled_start_at' => '2018-02-20 14:00:00',
                        'scheduled_end_at' => '2018-02-20 16:00:00',
                        'total_time_in_minutes' => 120,
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
        $employees = factory(Employee::class, $data['employees_to_create'])->create();

        $action = app(CreateNoveltiesToUsersAction::class);
        $result = $action->run([
            'employee_ids' => $employees->pluck('id')->all(),
            'novelties' => $data['novelties'],
        ]);

        $I->assertTrue($result);
        $employees->each(function ($employee) use ($I, $data) {
            foreach ($data['novelties'] as $novelty) {
                $I->seeRecord('novelties', [
                    'employee_id' => $employee->id,
                    'novelty_type_id' => $novelty['novelty_type_id'],
                    'scheduled_start_at' => $novelty['scheduled_start_at'],
                    'scheduled_end_at' => $novelty['scheduled_end_at'],
                    'total_time_in_minutes' => $novelty['total_time_in_minutes'],
                ]);
            }
        });
    }
}
