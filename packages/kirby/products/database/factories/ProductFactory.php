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
        'name' => $name = $faker->numerify('PROD-##'),
        'short_name' => $name,
        'internal_code' => $faker->unique()->numerify('internal-code-###'),
        'customer_code' => $faker->unique()->numerify('customer-code-###'),
        'wire_gauge_in_bwg' => $faker->bothify('### ???'),
        'wire_gauge_in_mm' => $faker->randomFloat(2, 1, 1000),
        'active' => true,
    ];
});
