<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Kirby\Authorization\Models\Permission;

/**
 * @internal
 */
class ProductsPackageSeed extends Seeder
{
    private array $permissions = [
        ['name' => 'products.create'],
        ['name' => 'products.search'],
    ];

    public function run()
    {
        array_walk($this->permissions, fn ($permission) => Permission::updateOrCreate(
            Arr::only($permission, ['name']),
            $permission
        ));
    }
}
