<?php

use Faker\Generator as Faker;
use llstarscreamll\WorkShifts\Models\WorkShift;

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
        "grace_minutes_for_start_times" => null,
        "grace_minutes_for_end_times" => null,
        "meal_time_in_minutes" => null,
        "min_minutes_required_to_discount_meal_time" => null,
        "applies_on_days" => [],
        "time_slots" => [],
    ];
});
