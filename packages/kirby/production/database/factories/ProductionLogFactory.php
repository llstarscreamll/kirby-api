<?php

use Faker\Generator as Faker;
use Kirby\Customers\Models\Customer;
use Kirby\Employees\Models\Employee;
use Kirby\Machines\Models\Machine;
use Kirby\Production\Enums\Purpose;
use Kirby\Production\Enums\Tag;
use Kirby\Production\Models\ProductionLog;
use Kirby\Products\Models\Product;

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

$factory->define(ProductionLog::class, function (Faker $faker) {
    return [
        'product_id' => fn () => factory(Product::class)->create()->id,
        'machine_id' => fn () => factory(Machine::class)->create()->id,
        'employee_id' => fn () => factory(Employee::class)->create()->id,
        'customer_id' => fn () => factory(Customer::class)->create()->id,
        'tag' => Tag::InLine(),
        'purpose' => Purpose::Consumption,
        'batch' => $faker->numerify('#######'),
        'tare_weight' => $tare = $faker->numberBetween(10, 150),
        'gross_weight' => $faker->numberBetween($tare, $tare * 5),
    ];
});
