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
 * @param  int  $cost_center_id
 * @param  string  $code
 * @param  string  $identification_number
 * @param  string  $first_name
 * @param  string  $last_name
 * @param  string  $full_name
 * @param  string  $position
 * @param  string  $location
 * @param  string  $address
 * @param  string  $phone
 * @param  float  $salary
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
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'int',
        'cost_center_id' => 'int',
        'code' => 'string',
        'identification_number' => 'string',
        'position' => 'string',
        'location' => 'string',
        'address' => 'string',
        'phone' => 'string',
        'salary' => 'float',
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
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['user:id,first_name,last_name,email'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['first_name', 'last_name', 'email'];

    // ######################################################################## #
    // Relations
    // ######################################################################## #

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
     * Employee identification token.
     *
     * @return mixed
     */
    public function token()
    {
        return $this->hasOne(Identification::class)->where('type', 'uuid');
    }

    /**
     * Related work shifts.
     *
     * @return mixed
     */
    public function workShifts()
    {
        return $this->belongsToMany(WorkShift::class)
            ->withTimestamps()
            ->withTrashed();
    }

    /**
     * @return mixed
     */
    public function timeClockLogs()
    {
        return $this->hasMany(TimeClockLog::class, 'employee_id');
    }

    // ######################################################################## #
    // Accessors
    // ######################################################################## #

    public function getFirstNameAttribute(): ?string
    {
        return $this->user->first_name;
    }

    public function getLastNameAttribute(): ?string
    {
        return $this->user->last_name;
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->user->first_name} {$this->user->last_name}";
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->user->phone_number;
    }

    public function getPhonePrefixAttribute(): ?string
    {
        return $this->user->phone_prefix;
    }

    public function getEmailAttribute(): ?string
    {
        return $this->user->email;
    }

    // ######################################################################## #
    // Methods
    // ######################################################################## #

    /**
     * @return WorkShift
     */
    public function getWorkShiftsThatMatchesTime(Carbon $time): ?Collection
    {
        $workShiftsMatchedBySlotTimesAndDays = $this->workShifts->reverse()
            ->filter(function (WorkShift $workShift) use ($time) {
                $matchedTimeSlots = collect($workShift->time_slots)
                    ->filter(function (array $timeSlot) use ($time, $workShift) {
                        [$hour, $seconds] = explode(':', $timeSlot['start']);
                        $slotStartFrom = now()
                            ->setTimezone($workShift->time_zone)
                            ->setTime($hour, $seconds)
                            ->subMinutes($workShift->grace_minutes_before_start_times);

                        [$hour, $seconds] = explode(':', $timeSlot['end']);
                        $slotEndTo = now()
                            ->setTimezone($workShift->time_zone)
                            ->setTime($hour, $seconds)
                            ->addMinutes($workShift->grace_minutes_after_end_times);

                        if ($slotStartFrom->hour > (int) $hour) {
                            $slotEndTo = $slotEndTo->addDay();
                        }

                        return $time->between($slotStartFrom, $slotEndTo) && (in_array($time->dayOfWeekIso, $workShift->applies_on_days) || 0 === count($workShift->applies_on_days));
                    });

                return $matchedTimeSlots->count();
            });

        if (0 === $workShiftsMatchedBySlotTimesAndDays->count()) {
            $workShiftsMatchedBySlotTimesAndDays = $this->workShifts->filter(function ($workShift) use ($time) {
                return (in_array($time->dayOfWeekIso, $workShift->applies_on_days) || 0 === count($workShift->applies_on_days)) && ! $time->greaterThan($workShift->getClosestSlotFlagTime('end', $time));
            });
        }

        return $workShiftsMatchedBySlotTimesAndDays;
    }
}
