<?php

use Illuminate\Database\Seeder;

/**
 * Class WorkShiftsPackageSeeder.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class WorkShiftsPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $this->call([
            WorkShiftsPermissionsSeeder::class,
            DefaultWorkShiftsSeeder::class,
        ]);
    }
}
