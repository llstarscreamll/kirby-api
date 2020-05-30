<?php

use Faker\Generator as Faker;
use Kirby\Company\Models\CostCenter;
use Kirby\Company\Models\SubCostCenter;

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

$factory->define(SubCostCenter::class, function (Faker $faker) {
    return [
        'cost_center_id' => factory(CostCenter::class)->create()->id,
        'code' => "{$faker->word}-{$faker->randomNumber($faker->numberBetween(4, 8))}",
        'name' => $faker->bothify('SCC-??###'),
    ];
});
