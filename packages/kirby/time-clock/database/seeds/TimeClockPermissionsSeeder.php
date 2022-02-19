<?php

use Illuminate\Database\Seeder;
use Kirby\Authorization\Models\Permission;

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
        ['name' => 'time-clock-logs.create'],
        ['name' => 'time-clock-logs.global-search'],
        ['name' => 'time-clock-logs.employee-search'],
        ['name' => 'time-clock-logs.check-in'],
        ['name' => 'time-clock-logs.check-out'],
        ['name' => 'time-clock-logs.approvals.create'],
        ['name' => 'time-clock-logs.approvals.delete'],
    ];

    /**
     * Run the database seeds.
     */
    public function run()
    {
        collect($this->permissions)->map(function ($permission) {
            return Permission::updateOrCreate($permission);
        });
    }
}
