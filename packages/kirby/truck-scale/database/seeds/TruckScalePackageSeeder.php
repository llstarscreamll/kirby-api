<?php

use Illuminate\Database\Seeder;
use Kirby\Authorization\Models\Permission;

class TruckScalePackageSeeder extends Seeder
{
    private $permissions = [
        ['name' => 'truck-scale.search'],
        ['name' => 'truck-scale.create'],
        ['name' => 'truck-scale.update'],
    ];

    public function run()
    {
        collect($this->permissions)->map(function ($permission) {
            return Permission::updateOrCreate($permission);
        });
    }
}
