<?php

use Illuminate\Database\Seeder;

/**
 * Class EmployeesPackageSeed.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class EmployeesPackageSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            EmployeesPermissionsSeeder::class,
        ]);
    }
}
