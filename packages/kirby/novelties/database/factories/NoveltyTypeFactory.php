<?php

use Faker\Generator as Faker;
use Kirby\Novelties\Enums\DayType;
use Kirby\Novelties\Enums\NoveltyTypeOperator;
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

$factory->define(NoveltyType::class, function (Faker $faker) {
    $randomCode = $faker->unique()->bothify('##??');

    return [
        'code' => "NT-$randomCode",
        'name' => "Novelty $randomCode",
        'context_type' => null,
        'time_zone' => 'UTC',
        'apply_on_days_of_type' => $faker->randomElement(DayType::getValues()),
        'apply_on_time_slots' => null,
        'operator' => $faker->randomElement(NoveltyTypeOperator::getValues()),
        'requires_comment' => $faker->boolean(),
        'keep_in_report' => $faker->boolean(),
    ];
});
