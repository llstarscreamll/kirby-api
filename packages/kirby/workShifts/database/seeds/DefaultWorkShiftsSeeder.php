<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Kirby\WorkShifts\Models\WorkShift;

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
            'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
            'time_zone' => 'America/Bogota',
            'time_slots' => [['start' => '06:00', 'end' => '14:00']],
        ],
        [
            'name' => '14-22',
            'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
            'time_zone' => 'America/Bogota',
            'time_slots' => [['start' => '14:00', 'end' => '22:00']],
        ],
        [
            'name' => '22-06',
            'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
            'time_zone' => 'America/Bogota',
            'time_slots' => [['start' => '22:00', 'end' => '06:00']],
        ],
        [
            'name' => '07-18',
            'applies_on_days' => [1, 2, 3, 4, 5], // monday to friday
            'time_zone' => 'America/Bogota',
            'time_slots' => [
                ['start' => '07:00', 'end' => '12:00'],
                ['start' => '13:00', 'end' => '18:00'],
            ],
        ],
        [
            'name' => '07-14',
            'applies_on_days' => [6], // sunday
            'time_zone' => 'America/Bogota',
            'time_slots' => [['start' => '07:00', 'end' => '14:00']],
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
            $keys = Arr::only($shift, ['name']);

            return WorkShift::updateOrCreate($keys, $shift);
        });
    }
}
