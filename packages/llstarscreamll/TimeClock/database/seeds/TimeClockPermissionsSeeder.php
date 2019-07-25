<?php

use Illuminate\Database\Seeder;
use llstarscreamll\Authorization\Models\Permission;

/**
 * Class TimeClockPermissionsSeeder.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class TimeClockPermissionsSeeder extends Seeder
{
    /**
     * @var array
     */
    private $permissions = [
        ['name' => 'time-clock-logs.approve'],
        ['name' => 'time-clock-logs.approvals.delete'],
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
