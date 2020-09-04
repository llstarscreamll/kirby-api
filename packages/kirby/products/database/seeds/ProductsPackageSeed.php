<?php

use Illuminate\Database\Seeder;

/**
 * Class ProductsPackageSeed.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class ProductsPackageSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            DefaultCategoriesSeed::class,
            DefaultProductsSeed::class,
        ]);
    }
}
