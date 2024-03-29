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
     */
    public function run()
    {
        $this->call([
            DefaultNoveltyTypesSeed::class,
            NoveltiesPermissionsSeeder::class,
            NoveltiesSettingsSeeder::class,
        ]);
    }
}
