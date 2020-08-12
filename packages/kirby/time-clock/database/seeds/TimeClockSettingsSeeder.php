<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Kirby\TimeClock\Models\Setting;

/**
 * Class TimeClockSettingsSeeder.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class TimeClockSettingsSeeder extends Seeder
{
    /**
     * @var array
     */
    private $settings = [
        [
            'key' => 'time-clock.require-novelty-type-for-non-punctual-checks',
            'name' => 'Require novelty type for non punctual checks',
            'description' => 'If true, then novelty type is required for non punctual checkin or checkout',
            'value' => "0",
        ],
        [
            'key' => 'time-clock.adjust-scheduled-novelty-datetime-based-on-checks',
            'name' => 'Adjust scheduled novelties datetime based on checks',
            'description' => 'If true, then scheduled novelties start/end datetime are adjusted relative to check in/out datetime',
            'value' => "1",
        ],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        collect($this->settings)->map(function ($setting) {
            $keys = Arr::only($setting, ['key']);

            return Setting::updateOrCreate($keys, $setting);
        });
    }
}
