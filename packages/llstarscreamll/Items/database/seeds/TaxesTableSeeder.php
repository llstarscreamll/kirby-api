<?php

use Illuminate\Database\Seeder;
use llstarscreamll\Items\Models\Tax;

class TaxesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Tax::create([
            'name'        => 'General',
            'description' => 'Tarifa general del IVA Colombiano',
            'percentage'  => 19,
        ]);
    }
}
