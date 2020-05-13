<?php

namespace Kirby\Novelties\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kirby\Company\Models\SubCostCenter;
use Kirby\Employees\Models\Employee;
use Kirby\Novelties\Enums\NoveltyTypeOperator;
use Kirby\TimeClock\Models\TimeClockLog;
use Kirby\Users\Models\User;

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
        'start_at',
        'end_at',
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
        'comment' => 'string',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['start_at', 'end_at', 'created_at', 'updated_at', 'deleted_at'];

    // ######################################################################## #
    // Relations
    // ######################################################################## #

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

    // ######################################################################## #
    // Accessors
    // ######################################################################## #

    /**
     * @return float
     */
    public function getTotalTimeInHoursAttribute(): float
    {
        $operator = $this->noveltyType->operator->is(NoveltyTypeOperator::Subtraction) ? -1 : 1;

        return round($this->start_at->diffInSeconds($this->end_at) / 60, 2) * $operator;
    }

    // ######################################################################## #
    // Methods
    // ######################################################################## #

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
