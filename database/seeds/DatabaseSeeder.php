<?php

use Illuminate\Database\Seeder;

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
            EmployeesPackageSeed::class,
            WorkShiftsPackageSeeder::class,
            TimeClockPackageSeeder::class,
            NoveltiesPackageSeed::class,
            ProductionPackageSeed::class,
            AuthorizationPackageSeeder::class,
            DefaultUserSeed::class,
        ]);
    }
}
