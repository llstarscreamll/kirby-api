<?php

use Illuminate\Database\Seeder;
use llstarscreamll\Authorization\Data\Seeders\AuthorizationPackageSeeder;
use llstarscreamll\WorkShifts\Data\Seeders\WorkShiftsPackageSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            WorkShiftsPackageSeeder::class,
            AuthorizationPackageSeeder::class,
        ]);
    }
}
