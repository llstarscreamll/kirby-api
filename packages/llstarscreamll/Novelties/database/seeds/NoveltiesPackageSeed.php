<?php

use Illuminate\Database\Seeder;

/**
 * Class NoveltiesPackageSeed.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class NoveltiesPackageSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            NoveltiesPermissionsSeeder::class,
            DefaultNoveltyTypesSeed::class,
        ]);
    }
}
