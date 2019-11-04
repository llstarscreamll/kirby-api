<?php

use Faker\Generator as Faker;
use Kirby\Employees\Models\Employee;
use Kirby\TimeClock\Models\TimeClockLog;
use Kirby\Users\Models\User;

$factory->define(TimeClockLog::class, function (Faker $faker) {
    $start = $faker->dateTime('1 day ago');
    $end = $faker->dateTimeInInterval($start, '+ 12 hours');

    return [
        'employee_id' => factory(Employee::class)->create()->id,
        'work_shift_id' => null,
        'checked_in_at' => $start,
        'check_in_novelty_type_id' => null,
        'checked_out_at' => $end,
        'check_out_novelty_type_id' => null,
        'checked_in_by_id' => $userId = factory(User::class)->create()->id,
        'checked_out_by_id' => $userId,
    ];
});
