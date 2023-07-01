<?php

use Faker\Generator as Faker;
use Kirby\TruckScale\Enums\VehicleType;
use Kirby\TruckScale\Enums\WeighingStatus;
use Kirby\TruckScale\Enums\WeighingType;
use Kirby\TruckScale\Models\Weighing;
use Kirby\Users\Models\User;

$factory->define(Weighing::class, function (Faker $faker) {
    return [
        'weighing_type' => $faker->randomElement(WeighingType::getKeys()),
        'vehicle_plate' => $faker->unique()->bothify('???###'),
        'vehicle_type' => $faker->randomElement(VehicleType::getKeys()),
        'driver_dni_number' => $faker->unique()->numerify('##########'),
        'driver_name' => $faker->name(),
        'client' => $faker->company(),
        'tare_weight' => $tare = $faker->numberBetween(10, 150),
        'gross_weight' => $faker->numberBetween($tare, $tare * 5),
        'weighing_description' => $faker->sentence(),
        'created_by_id' => factory(User::class),
        'updated_by_id' => factory(User::class),
        'status' => $faker->randomElement(WeighingStatus::getValues()),
    ];
});
