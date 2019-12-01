<?php

use Illuminate\Support\Arr;
use Illuminate\Database\Seeder;
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
        ['key' => 'time-clock.require-subtract-novelty-type-on-checks', 'value' => false],
        ['key' => 'time-clock.adjust-scheduled-novelties-times-based-on-checks', 'value' => false],
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
