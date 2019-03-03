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
            'name'       => '6-2',
            'start_time' => '06:00',
            'end_time'   => '14:00',
        ],
        [
            'name'       => '2-10',
            'start_time' => '14:00',
            'end_time'   => '22:00',
        ],
        [
            'name'       => '10-6',
            'start_time' => '22:00',
            'end_time'   => '06:00',
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
