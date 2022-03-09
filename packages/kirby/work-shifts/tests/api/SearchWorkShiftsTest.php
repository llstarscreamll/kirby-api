<?php

namespace Kirby\WorkShifts\Tests\api;

/**
 * Class SearchWorkShiftsTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 *
 * @internal
 */
class SearchWorkShiftsTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/work-shifts';

    /**
     * @var array
     */
    private $workShiftA;

    /**
     * @var array
     */
    private $workShiftB;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->workShiftA = [
            'name' => 'work shift A',
            'grace_minutes_before_start_times' => 15,
            'grace_minutes_after_start_times' => 15,
            'grace_minutes_before_end_times' => 15,
            'grace_minutes_after_end_times' => 15,
            'meal_time_in_minutes' => 90,
            'min_minutes_required_to_discount_meal_time' => 60 * 6,
            'time_slots' => json_encode([['start' => '07:00', 'end' => '12:30']]),
        ];

        $this->workShiftB = [
            'name' => 'work shift B',
            'grace_minutes_before_start_times' => 30,
            'grace_minutes_after_start_times' => 30,
            'grace_minutes_before_end_times' => 30,
            'grace_minutes_after_end_times' => 30,
            'meal_time_in_minutes' => 30,
            'min_minutes_required_to_discount_meal_time' => 60 * 4,
            'time_slots' => json_encode([['start' => '18:00', 'end' => '02:00']]),
        ];

        $this->haveRecord('work_shifts', $this->workShiftA);
        $this->haveRecord('work_shifts', $this->workShiftB);

        $this->actingAsAdmin();
    }

    /**
     * @test
     */
    public function whenRequestDataIsEmptyExpectOkWithAllResourcesPaginated()
    {
        $this->json('GET', $this->endpoint)
            ->assertOk()
            ->assertJsonHasPath('data.0')
            ->assertJsonHasPath('data.1')
            ->assertJsonHasPath('meta')
            ->assertJsonHasPath('links');
    }

    /**
     * @test
     */
    public function whenRequestDataHasSearchKeyOnFilterFieldExpectOkWithResourcesContainingTheGivenValueInTheNameAttribue()
    {
        $this->json('GET', $this->endpoint, ['filter' => ['search' => $this->workShiftA['name']]])
            ->assertOk()
            ->assertJsonHasPath('data.0')
            ->assertJsonMissingPath('data.1')
            ->assertJsonHasPath('meta')
            ->assertJsonHasPath('links');
    }
}
