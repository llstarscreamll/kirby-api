<?php

use Faker\Generator as Faker;
use Kirby\Company\Models\CostCenter;
use Kirby\Machines\Models\Machine;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
 */

$factory->define(Machine::class, function (Faker $faker) {
    return [
        'cost_center_id' => fn () => factory(CostCenter::class)->create()->id,
        'code' => $faker->unique()->word(),
        'name' => $faker->numerify('MACH-##'),
    ];
});
