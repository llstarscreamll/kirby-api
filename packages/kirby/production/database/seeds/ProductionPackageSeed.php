<?php

use Illuminate\Database\Seeder;

class ProductionPackageSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            ProductionPermissionsSeed::class,
        ]);
    }
}
