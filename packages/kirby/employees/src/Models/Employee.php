<?php

namespace Kirby\Employees\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Kirby\Company\Models\CostCenter;
use Kirby\TimeClock\Models\TimeClockLog;
use Kirby\Users\Models\User;
use Kirby\WorkShifts\Models\WorkShift;

/**
 * Class Employee.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class Employee extends Model
{
    use SoftDeletes;

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
        return $this->belongsTo(User::class, 'id')->withTrashed();
    }

    /**
     * Related cost center.
     *
     * @return mixed
     */
    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class)->withTrashed();
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
        return $this->belongsToMany(WorkShift::class)->withTrashed();
    }

    /**
     * @return mixed
     */
    public function timeClockLogs()
    {
        return $this->hasMany(TimeClockLog::class, 'employee_id');
    }

    /**
     * @param  Carbon      $time
     * @return WorkShift
     */
    public function getWorkShiftsThatMatchesTime(Carbon $time): ?Collection
    {
        $workShiftsMatchedBySlotTimesAndDays = $this->workShifts->filter(function (WorkShift $workShift) use ($time) {
            $matchedTimeSlots = collect($workShift->time_slots)->filter(function (array $timeSlot) use ($time, $workShift) {
                [$hour, $seconds] = explode(':', $timeSlot['start']);
                $slotStartFrom = now()->setTime($hour, $seconds)->subMinutes($workShift->grace_minutes_before_start_times);

                [$hour, $seconds] = explode(':', $timeSlot['end']);
                $slotEndTo = now()->setTime($hour, $seconds)->addMinutes($workShift->grace_minutes_after_end_times);

                if ($slotStartFrom->hour > (int) $hour) {
                    $slotEndTo = $slotEndTo->addDay();
                }

                return $time->between($slotStartFrom, $slotEndTo) && (in_array($time->dayOfWeekIso, $workShift->applies_on_days) || count($workShift->applies_on_days) === 0);
            });

            return $matchedTimeSlots->count();
        });

        if ($workShiftsMatchedBySlotTimesAndDays->count() === 0) {
            $workShiftsMatchedBySlotTimesAndDays = $this->workShifts->filter(function ($workShift) use ($time) {
                return (in_array($time->dayOfWeekIso, $workShift->applies_on_days) || count($workShift->applies_on_days) === 0) && ! $time->greaterThan($workShift->getClosestSlotFlagTime('end', $time));
            });
        }

        return $workShiftsMatchedBySlotTimesAndDays;
    }

    /**
     * @param  Carbon      $time
     * @return WorkShift
     */
    public function getWorkShiftsByClosestStartRangeTime(Carbon $time): ?Collection
    {
        return $this->workShifts->filter(function (WorkShift $workShift) use ($time) {
            $timeSlots = collect($workShift->time_slots)->filter(function (array $timeSlot) use ($time, $workShift) {
                [$hour, $seconds] = explode(':', $timeSlot['start']);
                $slotStartFrom = now()->setTime($hour, $seconds)->subMinutes($workShift->grace_minutes_before_start_times);
                $slotStartTo = now()->setTime($hour, $seconds)->addMinutes($workShift->grace_minutes_after_start_time);

                if ($slotStartFrom->hour > (int) $hour) {
                    $slotStartFrom = $slotStartFrom->subDay();
                    $slotStartTo = $slotStartTo->subDay();
                }

                return $time->between($slotStartFrom, $slotStartTo);
            });

            return $timeSlots->count() && in_array($time->dayOfWeekIso, $workShift->applies_on_days);
        });
    }
}
