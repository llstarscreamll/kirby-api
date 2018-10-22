<?php

use Carbon\Carbon;
use Faker\Generator as Faker;
use llstarscreamll\Customers\Models\Customer;
use llstarscreamll\Sales\Models\SaleStatus;
use llstarscreamll\Shippings\Models\Shipping;
use llstarscreamll\Stockrooms\Models\Stockroom;
use llstarscreamll\Users\Models\User;

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

$factory->define(llstarscreamll\Sales\Models\Sale::class, function (Faker $faker) {
    $date = Carbon::now();

    return [
        'seller_id'      => factory(User::class)->create(),
        'customer_id'    => factory(Customer::class)->create(),
        'shipping_to_id' => factory(Shipping::class)->create(),
        'stockroom_id'   => factory(Stockroom::class)->create(),
        'status_id'      => factory(SaleStatus::class)->create(),
        'issue_date'     => $date->toDateTimeString(),
        'shipment_date'  => $date->addDays($faker->randomDigit)->toDateTimeString(),
    ];
});
