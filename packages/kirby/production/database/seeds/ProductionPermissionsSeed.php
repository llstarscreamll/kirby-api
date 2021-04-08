<?php

use Illuminate\Database\Seeder;
use Kirby\Authorization\Models\Permission;

class ProductionPermissionsSeed extends Seeder
{
    /**
     * @var array
     */
    private $permissions = [
        ['name' => 'production-logs.create'],
        ['name' => 'production-logs.search'],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        array_walk($this->permissions, fn ($permission) => Permission::updateOrCreate($permission));
    }
}
