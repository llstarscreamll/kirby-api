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
        'start_time',
        'end_time',
        'grace_minutes_for_start_time',
        'grace_minutes_for_end_time',
        'meal_time_in_minutes',
        'min_minutes_required_to_discount_meal_time',
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
        'grace_minutes_for_start_time' => 'real',
        'grace_minutes_for_end_time' => 'real',
        'meal_time_in_minutes' => 'real',
        'min_minutes_required_to_discount_meal_time' => 'real',
    ];

}
