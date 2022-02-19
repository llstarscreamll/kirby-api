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
        'name' => "Work shift {$randomCode}",
        'grace_minutes_before_start_times' => 10,
        'grace_minutes_after_start_times' => 10,
        'grace_minutes_before_end_times' => 10,
        'grace_minutes_after_end_times' => 10,
        'meal_time_in_minutes' => 0,
        'min_minutes_required_to_discount_meal_time' => 0,
        'time_zone' => 'UTC',
        'applies_on_days' => [],
        'time_slots' => [],
    ];
});
