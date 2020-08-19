<?php

namespace Kirby\WorkShifts\Tests\api;

use Illuminate\Support\Arr;

/**
 * Class CreateWorkShiftTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateWorkShiftTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/work-shifts';

    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->actingAsAdmin();
    }

    /**
     * @test
     */
    public function whenRequestDataIsValidExpectCreatedWithResourceOnResponseAndDB()
    {
        $requestBody = [
            'name' => 'work shift one',
            'grace_minutes_before_start_times' => 15,
            'grace_minutes_after_start_times' => 15,
            'grace_minutes_before_end_times' => 15,
            'grace_minutes_after_end_times' => 15,
            'meal_time_in_minutes' => 90,
            'min_minutes_required_to_discount_meal_time' => 60 * 6,
            'time_slots' => [
                ['start' => '07:00', 'end' => '12:30'],
                ['start' => '02:00', 'end' => '06:00'],
            ],
        ];

        $this->json('POST', $this->endpoint, $requestBody)
            ->assertCreated()
            ->assertJsonHasPath('data.id');

        $this->assertDatabaseHas('work_shifts', Arr::except($requestBody, 'time_slots'));
        $this->assertDatabaseHas('work_shifts', [
            'name' => 'work shift one',
            'time_slots' => json_encode($requestBody['time_slots']),
        ]);
    }

    /**
     * @test
     */
    public function whenRequestDataIsEmptyExpectUnprocesableEntity()
    {
        $this->json('POST', $this->endpoint, [])
            ->assertStatus(422);
    }
}
