<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use llstarscreamll\TimeClock\Models\Setting;

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
        ['key' => 'time-clock.require-subtract-novelty-type-on-checks', 'value' => false],
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
