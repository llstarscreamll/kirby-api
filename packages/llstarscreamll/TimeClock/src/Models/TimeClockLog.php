<?php

namespace llstarscreamll\TimeClock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use llstarscreamll\WorkShifts\Models\WorkShift;

/**
 * Class TimeClockLog.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class TimeClockLog extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'employee_id',
        'work_shift_id',
        'checked_in_at',
        'check_in_novelty_type_id',
        'checked_out_at',
        'check_out_novelty_type_id',
        'checked_in_by_id',
        'checked_out_by_id',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'checked_in_at',
        'checked_out_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * @return mixed
     */
    public function workShift()
    {
        return $this->belongsTo(WorkShift::class);
    }
}
