<?php

namespace Kirby\WorkShifts\Tests\api;

use Illuminate\Support\Facades\DB;

/**
 * Class GetWorkShiftByIdTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class GetWorkShiftByIdTest extends \Tests\TestCase
{
    /**
     * @var string
     */
    private $endpoint = 'api/v1/work-shifts/:id';

    /**
     * @var array
     */
    private $workShift;

    
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
            'time_slots' => json_encode([['start' => '07:00', 'end' => '12:30'], ['start' => '02:00', 'end' => '06:00']]),
        ];

        $this->haveRecord('work_shifts', $this->workShift);
        $this->workShift['id'] = DB::table('work_shifts')->where('name', 'work shift A', )->first()->id;

        $this->actingAsAdmin();
    }

    /**
     * @test

     */
    public function whenIdExistsExpectOkWithSaidResourceAsJson()
    {
        $this->json('GET', str_replace(':id', $this->workShift['id'], $this->endpoint))
            ->assertOk()
            ->assertJsonPath('data.id', $this->workShift['id']);
    }

    /**
     * @test

     */
    public function whenIdDoesNotExistsExpectNotFound()
    {
        $this->json('GET', str_replace(':id', 123, $this->endpoint))
            ->assertNotFound();
    }
}
