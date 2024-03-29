<?php

use Illuminate\Database\Seeder;
use Kirby\Authorization\Models\Permission;

/**
 * Class EmployeesPermissionsSeeder.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class EmployeesPermissionsSeeder extends Seeder
{
    /**
     * @var array
     */
    private $permissions = [
        ['name' => 'employees.search'],
        ['name' => 'employees.create'],
        ['name' => 'employees.show'],
        ['name' => 'employees.update'],
        ['name' => 'employees.sync-by-csv-file'],
    ];

    /**
     * Run the database seeds.
     */
    public function run()
    {
        collect($this->permissions)->map(function ($permission) {
            return Permission::updateOrCreate($permission, $permission);
        });
    }
}
