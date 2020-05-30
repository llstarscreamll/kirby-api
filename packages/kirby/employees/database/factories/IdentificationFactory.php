<?php

use Faker\Generator as Faker;
use Kirby\Employees\Models\Employee;
use Kirby\Employees\Models\Identification;

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

$factory->define(Identification::class, function (Faker $faker) {
    return [
        'employee_id' => factory(Employee::class)->create()->id,
        'name' => $faker->randomElement(['E-card', 'PIN']),
        'code' => "{$faker->word}-{$faker->randomNumber($faker->numberBetween(4, 8))}",
    ];
});
