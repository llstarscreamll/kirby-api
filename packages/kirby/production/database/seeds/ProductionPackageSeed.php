<?php

use Illuminate\Database\Seeder;

class ProductionPackageSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $this->call([
            ProductionPermissionsSeed::class,
        ]);
    }
}
