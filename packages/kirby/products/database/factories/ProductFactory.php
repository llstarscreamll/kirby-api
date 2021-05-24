<?php

use Faker\Generator as Faker;
use Kirby\Products\Models\Product;

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

$factory->define(Product::class, function (Faker $faker) {
    return [
        'internal_code' => $faker->unique()->word(),
        'customer_code' => $faker->unique()->word(),
        'name' => $faker->numerify('PROD-##'),
        'wire_gauge_in_bwg' => $faker->numberBetween(1, 100),
        'wire_gauge_in_mm' => $faker->numberBetween(1, 100),
    ];
});
