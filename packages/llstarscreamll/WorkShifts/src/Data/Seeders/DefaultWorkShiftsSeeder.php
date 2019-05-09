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
            'name' => '06-14',
            'time_slots' => [['start' => '06:00', 'end' => '14:00']],
        ],
        [
            'name' => '14-22',
            'time_slots' => [['start' => '14:00', 'end' => '22:00']],
        ],
        [
            'name' => '22-06',
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
