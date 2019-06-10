<?php

use Faker\Generator as Faker;
use llstarscreamll\Users\Models\User;
use llstarscreamll\Employees\Models\Employee;
use llstarscreamll\WorkShifts\Models\WorkShift;
use llstarscreamll\Novelties\Models\NoveltyType;
use llstarscreamll\TimeClock\Models\TimeClockLog;

$factory->define(TimeClockLog::class, function (Faker $faker) {
    $start = $faker->dateTime('1 day ago');
    $end = $faker->dateTimeInInterval($start, '+ 12 hours');

    return [
        'employee_id' => factory(Employee::class)->make()->id,
        'work_shift_id' => factory(WorkShift::class)->make()->id,
        'checked_in_at' => $start,
        'check_in_novelty_type_id' => factory(NoveltyType::class)->make()->id,
        'checked_out_at' => $end,
        'check_out_novelty_type_id' => factory(NoveltyType::class)->make()->id,
        'checked_in_by_id' => $userId = factory(User::class)->make()->id,
        'checked_out_by_id' => $userId,
    ];
});
