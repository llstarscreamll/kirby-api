<?php

namespace Kirby\WorkShifts\Tests\api;

/**
 * Class CreateWorkShiftTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 *
 * @internal
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
            'time_zone' => 'America/Bogota',
            'applies_on_days' => [1, 2], // monday to friday
            'time_slots' => [
                ['start' => '07:00', 'end' => '12:30'],
                ['start' => '02:00', 'end' => '06:00'],
            ],
        ];

        $this->json('POST', $this->endpoint, $requestBody)
            ->assertCreated()
            ->assertJsonHasPath('data.id');

        $this->assertDatabaseHas('work_shifts', [
            'name' => 'work shift one',
            'grace_minutes_before_start_times' => 15,
            'grace_minutes_after_start_times' => 15,
            'grace_minutes_before_end_times' => 15,
            'grace_minutes_after_end_times' => 15,
            'meal_time_in_minutes' => 90,
            'min_minutes_required_to_discount_meal_time' => 60 * 6,
            'time_zone' => 'America/Bogota',
            'time_slots' => $this->castAsJson($requestBody['time_slots']),
            'applies_on_days' => $this->castAsJson($requestBody['applies_on_days']),
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
