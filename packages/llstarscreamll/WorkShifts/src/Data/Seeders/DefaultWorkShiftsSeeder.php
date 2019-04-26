<?php

namespace llstarscreamll\WorkShifts\Data\Seeders;

use Illuminate\Database\Seeder;
use llstarscreamll\WorkShifts\Models\WorkShift;

/**
 * Class DefaultWorkShiftsSeeder.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class DefaultWorkShiftsSeeder extends Seeder
{
    /**
     * @var array
     */
    private $defaultWorkShifts = [
        [
            'name' => '6-2',
            'time_slots' => [['start' => '06:00', 'end' => '14:00']],
        ],
        [
            'name' => '2-10',
            'time_slots' => [['start' => '14:00', 'end' => '22:00']],
        ],
        [
            'name' => '10-6',
            'time_slots' => [['start' => '22:00', 'end' => '06:00']],
        ],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        collect($this->defaultWorkShifts)->map(function ($shift) {
            $keys = array_only($shift, ['name']);

            return WorkShift::updateOrCreate($keys, $shift);
        });
    }
}
