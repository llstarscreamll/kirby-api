<?php

use Illuminate\Database\Seeder;
use Kirby\Novelties\Models\NoveltyType;
use Kirby\TimeClock\Models\Setting;

/**
 * Class NoveltiesSettingsSeeder.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class NoveltiesSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $settings = $this->settings();
        array_walk($settings, fn($setting) => Setting::where('key', $setting['key'])
                ->existsOr(fn() => Setting::create($setting))
        );
    }

    /**
     * @return array
     */
    private function settings(): array
    {
        return [
            [
                'key' => 'novelties.default-addition-novelty-type',
                'name' => 'Default addition novelty type',
                'description' => 'Default novelty type for addition operations',
                'data_type' => 'int',
                'value' => NoveltyType::where('code', 'HADI')->first()->id,
            ],
            [
                'key' => 'novelties.default-subtraction-novelty-type',
                'name' => 'Default subtraction novelty type',
                'description' => 'Default novelty type for subtraction operations',
                'data_type' => 'int',
                'value' => NoveltyType::where('code', 'PP')->first()->id,
            ],
            [
                'key' => 'novelties.default-addition-balance-novelty-type',
                'name' => 'Default addition balance novelty type ',
                'description' => 'Default novelty type for addition balance',
                'data_type' => 'int',
                'value' => NoveltyType::where('code', 'B+')->first()->id,
            ],
            [
                'key' => 'novelties.default-subtraction-balance-novelty-type',
                'name' => 'Default subtraction balance novelty type',
                'description' => 'Default novelty type for subtraction balance',
                'data_type' => 'int',
                'value' => NoveltyType::where('code', 'B-')->first()->id,
            ],
        ];}
}
