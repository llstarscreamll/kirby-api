<?php

use Faker\Generator as Faker;
use llstarscreamll\Employees\Models\Employee;
use llstarscreamll\Users\Models\User;

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

$factory->define(Employee::class, function (Faker $faker) {
    return [
        'id' => factory(User::class)->create()->id,
        'code' => "{$faker->word}-{$faker->randomNumber($faker->numberBetween(4, 8))}",
        'identification_number' => $faker->randomNumber($faker->numberBetween(5, 8)),
        'position' => $faker->jobTitle,
        'location' => $faker->city,
        'address' => $faker->address,
        'phone' => $faker->phoneNumber,
        'salary' => $faker->numberBetween(828116, 828116 * 10),
    ];
});
