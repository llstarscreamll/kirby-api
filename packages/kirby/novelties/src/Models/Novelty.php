<?php

namespace Kirby\Novelties\Models;

use Kirby\Users\Models\User;
use Kirby\Employees\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Kirby\Company\Models\SubCostCenter;
use Kirby\TimeClock\Models\TimeClockLog;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Novelty.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class Novelty extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'employee_id',
        'novelty_type_id',
        'sub_cost_center_id',
        'time_clock_log_id',
        'scheduled_start_at',
        'scheduled_end_at',
        'total_time_in_minutes',
        'comment',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'employee_id' => 'int',
        'novelty_type_id' => 'int',
        'sub_cost_center_id' => 'int',
        'time_clock_log_id' => 'int',
        'total_time_in_minutes' => 'int',
        'comment' => 'string',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['scheduled_start_at', 'scheduled_end_at', 'created_at', 'updated_at', 'deleted_at'];

    // ####################################################################### #
    //                                 Relations                               #
    // ####################################################################### #

    /**
     * @return mixed
     */
    public function noveltyType()
    {
        return $this->belongsTo(NoveltyType::class)->withTrashed();
    }

    /**
     * @return mixed
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    /**
     * @return mixed
     */
    public function subCostCenter()
    {
        return $this->belongsTo(SubCostCenter::class)->withTrashed();
    }

    /**
     * @return mixed
     */
    public function timeClockLog()
    {
        return $this->belongsTo(TimeClockLog::class)->withTrashed();
    }

    /**
     * @return mixed
     */
    public function approvals()
    {
        return $this->belongsToMany(User::class, 'novelty_approvals')->withTimestamps()->withTrashed();
    }

    // ####################################################################### #
    //                                 Accessors                               #
    // ####################################################################### #

    /**
     * @return float
     */
    public function getTotalTimeInHoursAttribute(): float
    {
        return round($this->attributes['total_time_in_minutes'] / 60, 2);
    }

    // ####################################################################### #
    //                                  Methods                                #
    // ####################################################################### #

    /**
     * @param  int    $approverId
     * @return void
     */
    public function approve(int $approverId)
    {
        $this->approvals()->sync($approverId, false);
    }

    /**
     * @param  int    $approverId
     * @return void
     */
    public function deleteApprove(int $approverId)
    {
        $this->approvals()->detach($approverId);
    }
}
