<?php

use Faker\Generator as Faker;
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
        'operator' => $faker->randomElement(NoveltyTypeOperator::getValues()),
        'requires_comment' => $faker->boolean(),
    ];
});
