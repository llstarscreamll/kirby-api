<?php

use Illuminate\Database\Seeder;
use llstarscreamll\Novelties\Seeds\DefaultNoveltyTypesSeed;
use llstarscreamll\WorkShifts\Data\Seeders\WorkShiftsPackageSeeder;
use llstarscreamll\Authorization\Data\Seeders\AuthorizationPackageSeeder;

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
            AuthorizationPackageSeeder::class,
            WorkShiftsPackageSeeder::class,
            DefaultNoveltyTypesSeed::class,
        ]);
    }
}
