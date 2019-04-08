<?php
namespace llstarscreamll\WorkShifts\Data\Seeders;

use Illuminate\Database\Seeder;
use llstarscreamll\WorkShifts\Models\WorkShift;

/**
 * Class WorkShiftsPackageSeeder.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class WorkShiftsPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            WorkShiftsPermissionsSeeder::class,
            DefaultWorkShiftsSeeder::class,
        ]);
    }
}