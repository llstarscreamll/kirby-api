<?php

use Faker\Generator as Faker;
use llstarscreamll\Items\Models\MeasureUnit;
use llstarscreamll\Items\Models\Tax;

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

$factory->define(llstarscreamll\Items\Models\Item::class, function (Faker $faker) {
    return [
        'name'            => $faker->words(3, true),
        'description'     => $faker->word(6, true),
        'measure_unit_id' => factory(MeasureUnit::class)->create()->id,
        'sale_price'      => $min = $faker->randomNumber(),
        'purchase_price'  => $faker->numberBetween($min, $min * $faker->randomFloat($decimals = 2, $min = 1, $max = 3)),
        'tax_id'          => factory(Tax::class)->create()->id,
    ];
});
