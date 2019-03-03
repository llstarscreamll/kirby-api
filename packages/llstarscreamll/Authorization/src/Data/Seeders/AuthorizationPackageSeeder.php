<?php
namespace llstarscreamll\Authorization\Data\Seeders;

use Illuminate\Database\Seeder;
use llstarscreamll\Authorization\Models\Permission;
use llstarscreamll\Authorization\Models\Role;

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
                $keys = array_only($role, ['name']);

                return Role::updateOrCreate($keys, $role);
            })
            ->first(function ($role) {return $role->name === 'admin';})
            ->syncPermissions(Permission::all());
    }
}
