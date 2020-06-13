<?php

use Illuminate\Database\Seeder;
use Kirby\Authorization\Models\Permission;

/**
 * Class NoveltiesPermissionsSeeder.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class NoveltiesPermissionsSeeder extends Seeder
{
    /**
     * @var array
     */
    private $permissions = [
        // novelties
        ['name' => 'novelties.get'],
        ['name' => 'novelties.search'],
        ['name' => 'novelties.update'],
        ['name' => 'novelties.delete'],
        ['name' => 'novelties.create-many'],
        ['name' => 'novelties.report-by-employee'],
        ['name' => 'novelties.export'],
        ['name' => 'novelties.create-approvals-by-employee-and-date-range'],
        ['name' => 'novelties.delete-approvals-by-employee-and-date-range'],
        // novelty approvals
        ['name' => 'novelties.approvals.create'],
        ['name' => 'novelties.approvals.delete'],
        // novelty types
        ['name' => 'novelty-types.get'],
        ['name' => 'novelty-types.search'],
        ['name' => 'novelty-types.update'],
        ['name' => 'novelty-types.delete'],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        collect($this->permissions)->map(function ($permission) {
            return Permission::updateOrCreate($permission, $permission);
        });
    }
}
