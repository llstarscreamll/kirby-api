<?php
namespace llstarscreamll\WorkShifts\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WorkShift.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class WorkShift extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'grace_minutes_for_start_time',
        'grace_minutes_for_end_time',
        'meal_time_in_minutes',
        'min_minutes_required_to_discount_meal_time',
    ];
}
