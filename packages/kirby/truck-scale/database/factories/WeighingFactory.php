<?php

use Faker\Generator as Faker;
use Kirby\TruckScale\Enums\VehicleType;
use Kirby\TruckScale\Models\Weighing;

$factory->define(Weighing::class, function (Faker $faker) {
    return [
        'vehicle_plate' => $faker->unique()->bothify('???###'),
        'vehicle_type' => $faker->randomElement(VehicleType::getKeys()),
        'driver_dni_number' => $faker->unique()->numerify('##########'),
        'driver_name' => $faker->name(),
    ];
});
