<?php

use Faker\Generator as Faker;
use Kirby\WorkShifts\Models\WorkShift;

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

$factory->define(WorkShift::class, function (Faker $faker) {
    $randomCode = $faker->bothify('##??');

    return [
        'name' => "Work shift $randomCode",
        'applies_on_days' => [],
        'time_slots' => [],
    ];
});
