<?php

namespace llstarscreamll\WorkShifts\Models;

use Carbon\Carbon;
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
        'applies_on_days',
        'min_minutes_required_to_discount_meal_time',
        'time_slots',
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
        'time_slots' => 'array',
    ];

    /**
     * Character to be used as days separator on `applies_on_days` column.
     *
     * @var string
     */
    private $daysSeparator = '|';

    /**
     * `applies_on_days` attribute is used as array but stored as string.
     *
     * @param  array  $appliesOnDays
     * @return void
     */
    public function setAppliesOnDaysAttribute(array $appliesOnDays): void
    {
        $this->attributes['applies_on_days'] = implode($this->daysSeparator, $appliesOnDays);
    }

    /**
     * `applies_on_days` attribute is used as array but stored as string.
     *
     * @return array
     */
    public function getAppliesOnDaysAttribute(): array
    {
        $daysNumbers = explode($this->daysSeparator, $this->attributes['applies_on_days']);

        return array_map('intval', $daysNumbers);
    }

    /**
     * @param  Carbon $time
     * @return bool
     */
    public function isOnTimeToStart(Carbon $time = null): bool
    {
        $time = $time ?? now();

        return collect($this->time_slots)->filter(function (array $timeSlot) use ($time) {
            [$hour, $seconds] = explode(':', $timeSlot['start']);
            $slotStartFrom = now()->setTime($hour, $seconds)->subMinutes($this->grace_minutes_for_start_time);
            $slotStartTo = now()->setTime($hour, $seconds)->addMinutes($this->grace_minutes_for_start_time);

            return $time->between($slotStartFrom, $slotStartTo);
        })->count() > 0;
    }

    /**
     * @param  Carbon $time
     * @return bool
     */
    public function isOnTimeToEnd(Carbon $time = null): bool
    {
        $time = $time ?? now();

        return collect($this->time_slots)->filter(function (array $timeSlot) use ($time) {
            [$hour, $seconds] = explode(':', $timeSlot['end']);
            $slotEndFrom = now()->setTime($hour, $seconds)->subMinutes($this->grace_minutes_for_start_time);
            $slotEndTo = now()->setTime($hour, $seconds)->addMinutes($this->grace_minutes_for_start_time);

            return $time->between($slotEndFrom, $slotEndTo);
        })->count() > 0;
    }
}
