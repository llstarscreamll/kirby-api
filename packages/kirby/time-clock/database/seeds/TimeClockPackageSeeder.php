<?php

use Illuminate\Database\Seeder;

/**
 * Class TimeClockPackageSeeder.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class TimeClockPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $this->call([
            TimeClockSettingsSeeder::class,
            TimeClockPermissionsSeeder::class,
        ]);
    }
}
