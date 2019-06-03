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
        'grace_minutes_for_start_times' => 'int',
        'grace_minutes_for_end_times' => 'int',
        'meal_time_in_minutes' => 'int',
        'min_minutes_required_to_discount_meal_time' => 'int',
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
     * @return int
     */
    public function isOnTimeToStart(Carbon $time = null): int
    {
        return $this->isOnTimeOnSlot('start', $time ?? now());
    }

    /**
     * @param  Carbon $time
     * @return int
     */
    public function isOnTimeToEnd(Carbon $time = null): int
    {
        return $this->isOnTimeOnSlot('end', $time ?? now());
    }

    /**
     * @param  string $flag   'start'|'end'
     * @param  Carbon $time
     * @return int    -1 too early, on time, 1 too late
     */
    public function isOnTimeOnSlot(string $flag, Carbon $time): int
    {
        return collect($this->time_slots)
            ->map(function ($timeSlot) {
                [$hour, $seconds] = explode(':', $timeSlot['start']);
                $start = now()->setTime($hour, $seconds)->subMinutes($this->grace_minutes_for_start_times);

                [$hour, $seconds] = explode(':', $timeSlot['end']);
                $end = now()->setTime($hour, $seconds)->subMinutes($this->grace_minutes_for_end_times);

                return [
                    'end' => $end,
                    'start' => $start,
                ];
            })
            ->sortBy(function (array $timeSlot) use ($time, $flag) {
                return $time->diffInSeconds($timeSlot[$flag]);
            })
            ->map(function (array $timeSlot) use ($time, $flag) {
                $flagGraceFrom = $timeSlot[$flag]->copy()->subMinutes($this->grace_minutes_for_end_times);
                $flagGraceTo = $timeSlot[$flag]->copy()->addMinutes($this->grace_minutes_for_end_times);

                if ($time->between($flagGraceFrom, $flagGraceTo)) {
                    return 0;
                }

                return $time->lessThan($flagGraceFrom) ? -1 : 1;
            })->first();
    }
}
