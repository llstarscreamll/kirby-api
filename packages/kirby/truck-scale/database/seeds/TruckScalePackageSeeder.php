<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Kirby\Authorization\Models\Permission;
use Kirby\TimeClock\Models\Setting;

class TruckScalePackageSeeder extends Seeder
{
    private $permissions = [
        ['name' => 'truck-scale.search'],
        ['name' => 'truck-scale.create'],
        ['name' => 'truck-scale.update'],
        ['name' => 'truck-scale.cancel'],
        ['name' => 'truck-scale.export'],
        ['name' => 'truck-scale.update-settings'],
    ];

    private $settings = [
        [
            'key' => 'truck-scale.require-weighing-machine-lecture',
            'name' => 'Require weight machine lecture',
            'description' => 'When value is ON, in desktop app the truck scale machine will fill the weight form fields automatically with no way to fill manually those values, when value is OFF values can be filled manually',
            'data_type' => 'string',
            'value' => 'ON',
        ],
    ];

    public function run()
    {
        collect($this->permissions)->map(function ($permission) {
            return Permission::firstOrCreate($permission);
        });

        collect($this->settings)->each(fn ($s) => Setting::firstOrCreate(Arr::only($s, ['key']), $s));
    }
}
