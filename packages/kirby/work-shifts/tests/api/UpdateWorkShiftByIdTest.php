<?php

namespace Kirby\WorkShifts\Tests\api;

use Illuminate\Support\Facades\DB;

/**
 * Class UpdateWorkShiftByIdTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class UpdateWorkShiftByIdTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/work-shifts/:id';

    /**
     * @var array
     */
    private $workShift;

    /**
     * @var array
     */
    private $requestData = [
        'name' => 'updated work shift',
        'grace_minutes_before_start_times' => 45,
        'grace_minutes_after_start_times' => 45,
        'grace_minutes_before_end_times' => 45,
        'grace_minutes_after_end_times' => 45,
        'meal_time_in_minutes' => 45,
        'min_minutes_required_to_discount_meal_time' => 30 * 2,
        'time_zone' => 'America/Bogota',
        'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
        'time_slots' => [['start' => '07:00', 'end' => '12:30']],
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->workShift = [
            'name' => 'work shift A',
            'grace_minutes_before_start_times' => 15,
            'grace_minutes_after_start_times' => 15,
            'grace_minutes_before_end_times' => 15,
            'grace_minutes_after_end_times' => 15,
            'meal_time_in_minutes' => 90,
            'min_minutes_required_to_discount_meal_time' => 60 * 6,
            'time_zone' => 'UTC',
            'applies_on_days' => json_encode([6, 7]),
            'time_slots' => json_encode([['start' => '07:00', 'end' => '12:30'], ['start' => '02:00', 'end' => '06:00']]),
        ];

        $this->haveRecord('work_shifts', $this->workShift);
        $this->workShift['id'] = DB::table('work_shifts')->where('name', 'work shift A', )->first()->id;

        $this->actingAsAdmin();
    }

    /**
     * @test
     */
    public function whenIdExistsAndRequestDataIsValidExpectOkWithResourceUpdatedInResponseAndDB()
    {
        $this->json('PUT', str_replace(':id', $this->workShift['id'], $this->endpoint), $this->requestData)
            ->assertOk()
            ->assertJsonPath('data.id', (int) $this->workShift['id']);

        $this->assertDatabaseHas('work_shifts',
            ['time_slots' => json_encode($this->requestData['time_slots'])] +
            ['applies_on_days' => json_encode($this->requestData['applies_on_days'])] +
            $this->requestData);
    }

    /**
     * @test
     */
    public function whenIdDoesNotExistsExpectNotFound()
    {
        $this->json('PUT', str_replace(':id', 123, $this->endpoint), $this->requestData)
            ->assertNotFound();
    }
}
