<?php

namespace WorkShifts;

use Illuminate\Support\Facades\DB;

/**
 * Class DeleteWorkShiftByIdTest.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class DeleteWorkShiftByIdTest extends \Tests\TestCase
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
            'grace_minutes_after_start_times' => 15,
            'grace_minutes_before_start_times' => 15,
            'grace_minutes_after_end_times' => 15,
            'grace_minutes_before_end_times' => 15,
            'meal_time_in_minutes' => 90,
            'min_minutes_required_to_discount_meal_time' => 60 * 6,
            'time_slots' => json_encode([['start' => '07:00', 'end' => '12:30'], ['start' => '02:00', 'end' => '06:00']]),
            'deleted_at' => null,
        ];

        $this->haveRecord('work_shifts', $this->workShift);
        $this->workShift['id'] = DB::table('work_shifts')->where('name', 'work shift A', )->first()->id;

        $this->actingAsAdmin();
    }

    /**
     * @test

     */
    public function whenIdExistsExpectNoContentAndResourceToBeDeleted()
    {
        $this->json('DELETE', str_replace(':id', $this->workShift['id'], $this->endpoint))
            ->assertStatus(204);
        $this->assertDatabaseMissing('work_shifts', $this->workShift);
    }

    /**
     * @test

     */
    public function whenIdDoesNotExistsExpectNotFound()
    {
        $this->json('DELETE', str_replace(':id', 123, $this->endpoint))
            ->assertNotFound();
    }
}
