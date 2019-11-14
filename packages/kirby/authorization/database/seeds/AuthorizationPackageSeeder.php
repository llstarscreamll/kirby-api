<?php

use Illuminate\Support\Arr;
use Illuminate\Database\Seeder;
use Kirby\Authorization\Models\Role;
use Kirby\Authorization\Models\Permission;

/**
 * Class AuthorizationPackageSeeder.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class AuthorizationPackageSeeder extends Seeder
{
    /**
     * @var array
     */
    private $defaultRoles = [
        ['name' => 'admin', 'display_name' => 'Administrator'],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        collect($this->defaultRoles)
            ->map(function ($role) {
                $keys = Arr::only($role, ['name']);

                return Role::updateOrCreate($keys, $role);
            })
            ->first(function ($role) {
                return $role->name === 'admin';
            })
            ->syncPermissions(Permission::all());
    }
}
