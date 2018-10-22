<?php

use Illuminate\Database\Seeder;
use llstarscreamll\Items\Models\MeasureUnit;

class MeasureUnitsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        MeasureUnit::create([
            'name'    => 'Unidad',
            'symbol'  => 'UND',
            'default' => true,
        ]);
    }
}
