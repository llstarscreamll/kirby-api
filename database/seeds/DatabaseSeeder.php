<?php

use Illuminate\Database\Seeder;
use llstarscreamll\Authorization\Data\Seeders\AuthorizationPackageSeeder;
use llstarscreamll\Novelties\Seeds\DefaultNoveltyTypesSeed;
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
            EmployeesPackageSeed::class,
            WorkShiftsPackageSeeder::class,
            DefaultNoveltyTypesSeed::class,
            TimeClockPackageSeeder::class,
            llstarscreamll\Novelties\Seeds\NoveltiesPackageSeed::class,
            AuthorizationPackageSeeder::class,
            DefaultUserSeed::class,
        ]);
    }
}
