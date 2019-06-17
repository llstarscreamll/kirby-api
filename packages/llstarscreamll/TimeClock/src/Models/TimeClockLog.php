<?php

namespace llstarscreamll\TimeClock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use llstarscreamll\Employees\Models\Employee;
use llstarscreamll\WorkShifts\Models\WorkShift;
use llstarscreamll\Novelties\Models\NoveltyType;

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
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'employee_id' => 'int',
        'work_shift_id' => 'int',
        'check_in_novelty_type_id' => 'int',
        'check_out_novelty_type_id' => 'int',
        'checked_in_by_id' => 'int',
        'checked_out_by_id' => 'int',
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

    # ######################################################################## #
    #                                 Relations                                #
    # ######################################################################## #

    /**
     * @return mixed
     */
    public function workShift()
    {
        return $this->belongsTo(WorkShift::class);
    }

    /**
     * @return mixed
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return mixed
     */
    public function checkInNovelty()
    {
        return $this->belongsTo(NoveltyType::class, 'check_in_novelty_type_id');
    }

    /**
     * @return mixed
     */
    public function checkOutNovelty()
    {
        return $this->belongsTo(NoveltyType::class, 'check_out_novelty_type_id');
    }

    # ######################################################################## #
    #                                 Accessors                                #
    # ######################################################################## #

    /**
     * @return float
     */
    public function getClockedMinutesAttribute(): float
    {
        return $this->checked_in_at->diffInMinutes($this->checked_out_at);
    }
}
