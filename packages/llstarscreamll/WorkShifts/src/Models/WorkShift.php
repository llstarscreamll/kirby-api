<?php

namespace llstarscreamll\WorkShifts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class WorkShift.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class WorkShift extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'grace_minutes_for_start_times',
        'grace_minutes_for_end_times',
        'meal_time_in_minutes',
        'min_minutes_required_to_discount_meal_time',
        'time-slots',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'grace_minutes_for_start_times' => 'real',
        'grace_minutes_for_end_times' => 'real',
        'meal_time_in_minutes' => 'real',
        'min_minutes_required_to_discount_meal_time' => 'real',
        'time-slots' => 'array',
    ];
}
