<?php

namespace llstarscreamll\Employees\Models;

use Carbon\Carbon;
use llstarscreamll\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use llstarscreamll\WorkShifts\Models\WorkShift;
use llstarscreamll\TimeClock\Models\TimeClockLog;

/**
 * Class Employee.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class Employee extends Model
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'cost_center_id',
        'code',
        'identification_number',
        'position',
        'location',
        'address',
        'phone',
        'salary',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * Related user.
     *
     * @return mixed
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'id');
    }

    /**
     * Related identifications.
     *
     * @return mixed
     */
    public function identifications()
    {
        return $this->hasMany(Identification::class);
    }

    /**
     * Related work shifts.
     *
     * @return mixed
     */
    public function workShifts()
    {
        return $this->belongsToMany(WorkShift::class);
    }

    /**
     * @return mixed
     */
    public function timeClockLogs()
    {
        return $this->hasMany(TimeClockLog::class, 'employee_id');
    }

    /**
     * @param  Carbon      $start
     * @param  Carbon      $end
     * @return WorkShift
     */
    public function getFirstWorkShiftByClosestRangeTime(Carbon $start, Carbon $end):  ? WorkShift
    {
        return $this->workShifts->first(function (WorkShift $workShift) use ($start, $end) {
            $timeSlots = collect($workShift->time_slots)->filter(function (array $timeSlot) use ($start, $end, $workShift) {
                [$hour, $seconds] = explode(':', $timeSlot['start']);
                $slotStartFrom = now()->setTime($hour, $seconds)->subMinutes($workShift->grace_minutes_for_start_time);
                $slotStartTo = now()->setTime($hour, $seconds)->addMinutes($workShift->grace_minutes_for_start_time);

                [$hour, $seconds] = explode(':', $timeSlot['end']);
                $slotEndFrom = now()->setTime($hour, $seconds)->subMinutes($workShift->grace_minutes_for_end_time);
                $slotEndTo = now()->setTime($hour, $seconds)->addMinutes($workShift->grace_minutes_for_end_time);

                if ($slotStartFrom->hour > (int) $hour) {
                    $slotStartFrom = $slotStartFrom->subDay();
                    $slotStartTo = $slotStartTo->subDay();
                }

                return $start->between($slotStartFrom, $slotStartTo) && $end->between($slotEndFrom, $slotEndTo);
            });

            return $timeSlots->count();
        });
    }
}
