<?php

use Faker\Generator as Faker;

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

$factory->define(llstarscreamll\Customers\Models\Customer::class, function (Faker $faker) {
    return [
        'name'            => $faker->name,
        'document_number' => $faker->randomNumber(12),
        'email'           => $faker->email,
        'phone'           => $faker->tollFreePhoneNumber,
    ];
});
