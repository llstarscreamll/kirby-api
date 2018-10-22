<?php

use Faker\Generator as Faker;
use llstarscreamll\Stockrooms\Models\Stockroom;

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

$factory->define(Stockroom::class, function (Faker $faker) {
    return [
        'name'    => $faker->word,
        'address' => $faker->address,
    ];
});
