<?php

use Faker\Generator as Faker;
use Kirby\Employees\Models\Employee;
use Kirby\Novelties\Models\Novelty;
use Kirby\Novelties\Models\NoveltyType;

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

$factory->define(Novelty::class, function (Faker $faker) {
    return [
        'time_clock_log_id' => null,
        'employee_id' => factory(Employee::class)->create()->id,
        'novelty_type_id' => factory(NoveltyType::class)->create()->id,
        'total_time_in_minutes' => $faker->numberBetween(100, 1000),
    ];
});
