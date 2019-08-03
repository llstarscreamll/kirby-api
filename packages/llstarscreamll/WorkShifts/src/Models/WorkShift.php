<?php

namespace llstarscreamll\WorkShifts\Models;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
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
        'grace_minutes_before_start_times',
        'grace_minutes_after_start_times',
        'grace_minutes_before_end_times',
        'grace_minutes_after_end_times',
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
        'grace_minutes_before_start_times' => 'int',
        'grace_minutes_after_start_times' => 'int',
        'grace_minutes_before_end_times' => 'int',
        'grace_minutes_after_end_times' => 'int',
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

    // ######################################################################## #
    //                                  Mutators                                #
    // ######################################################################## #

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

    // ######################################################################## #
    //                                 Accessors                                #
    // ######################################################################## #

    /**
     * `applies_on_days` attribute is used as array but stored as string.
     *
     * @return array
     */
    public function getAppliesOnDaysAttribute(): array
    {
        $daysNumbers = $this->attributes['applies_on_days']
            ? explode($this->daysSeparator, $this->attributes['applies_on_days'])
            : [];

        return array_map('intval', $daysNumbers);
    }

    /**
     * @todo return time in minutes, not hours
     * @return int diff in hours
     */
    public function getTotalTimeAttribute()
    {
        $totalTime = 0;

        $slots = new Collection($this->time_slots ?? []);
        $slots = $slots->map(function ($slot) {
            return $this->mapTimeSlot($slot);
        });
        $firstSlot = $slots->first();
        $lastSlot = $slots->last();
        $start = Arr::get($firstSlot, 'start');
        $end = Arr::get($lastSlot, 'end');

        if ($start || $end) {
            $totalTime = $start->diffInHours($end) - ($this->meal_time_in_minutes / 60);
        }

        return $totalTime;
    }

    // ######################################################################## #
    //                                Methods                                   #
    // ######################################################################## #

    /**
     * @param  Carbon $time
     * @return int
     */
    public function startPunctuality(Carbon $time = null): int
    {
        return $this->slotPunctuality('start', $time ?? now());
    }

    /**
     * @param  Carbon $time
     * @return int
     */
    public function endPunctuality(Carbon $time = null): int
    {
        return $this->slotPunctuality('end', $time ?? now());
    }

    /**
     * @param  string $flag   'start'|'end'
     * @param  Carbon $time
     * @return int    -1 early, zero on time, 1 late
     */
    public function slotPunctuality(string $flag, Carbon $time): ?int
    {
        return collect($this->time_slots)
            ->map(function ($timeSlot) use ($time, $flag) {
                return $this->mapTimeSlot($timeSlot, $time, $beGraceTimeAware = false, $flag === 'end');
            })
            ->sortBy(function (array $timeSlot) use ($time, $flag) {
                return $time->diffInSeconds($timeSlot[$flag]);
            })
            ->map(function (array $timeSlot) use ($time, $flag) {
                $flagGraceFrom = $timeSlot[$flag]->copy()->subMinutes($this->{"grace_minutes_before_{$flag}_times"});
                $flagGraceTo = $timeSlot[$flag]->copy()->addMinutes($this->{"grace_minutes_after_{$flag}_times"});

                if ($time->between($flagGraceFrom, $flagGraceTo)) {
                    return 0;
                }

                return $time->lessThan($flagGraceFrom) ? -1 : 1;
            })->first();
    }

    /**
     * @param array  $timeSlot
     * @param Carbon $date
     */
    private function mapTimeSlot(array $timeSlot, Carbon $date = null, bool $beGraceTimeAware = true, bool $relativeToEnd = false): array
    {
        $date = $date ?? now();

        [$hour, $seconds] = explode(':', $timeSlot['start']);
        $start = $date->copy()->setTime($hour, $seconds);

        [$hour, $seconds] = explode(':', $timeSlot['end']);
        $end = $date->copy()->setTime($hour, $seconds);

        if ($beGraceTimeAware) {
            $start = $start->subMinutes($this->grace_minutes_before_start_times);
            $end = $end->addMinutes($this->grace_minutes_after_end_times);
        }

        if ($start->greaterThan($end) && ! $relativeToEnd) {
            $end = $end->addDay();
        }

        if ($relativeToEnd) {
            $start = $start->subDay();
        }

        return [
            'end' => $end,
            'start' => $start,
        ];
    }

    /**
     * @param  string  $flag
     * @param  Carbon  $time
     * @return mixed
     */
    public function getClosestSlotFlagTime(string $flag, Carbon $time): ?Carbon
    {
        $slot = collect($this->time_slots)
            ->map(function (array $timeSlot) use ($time, $flag) {
                $timeSlot = $this->mapTimeSlot($timeSlot, $time, $beGraceTimeAware = false, $flag === 'end');
                $timeSlot['diff'] = $time->diffInMinutes($timeSlot[$flag]);

                return $timeSlot;
            })->sortBy('diff')->first();

        return $slot[$flag] ?? null;
    }

    /**
     * @param Carbon $relativeToTime
     */
    public function minStartTimeSlot(Carbon $relativeToTime = null, $beGraceTimeAware = false): ?Carbon
    {
        $relativeToTime = $relativeToTime ?? now();

        return collect($this->time_slots)
            ->map(function (array $timeSlot) use ($relativeToTime, $beGraceTimeAware) {
                $timeSlot = $this->mapTimeSlot($timeSlot, $relativeToTime, $beGraceTimeAware);

                return $timeSlot['start'];
            })->sort()->first();
    }

    /**
     * @param Carbon $relativeToTime
     */
    public function maxEndTimeSlot(Carbon $relativeToTime = null, $beGraceTimeAware = false, $relativeToEnd = true)
    {
        $relativeToTime = $relativeToTime ?? now();

        return collect($this->time_slots)
            ->map(function (array $timeSlot) use ($relativeToTime, $beGraceTimeAware, $relativeToEnd) {
                $timeSlot = $this->mapTimeSlot($timeSlot, $relativeToTime, $beGraceTimeAware, $relativeToEnd);

                return $timeSlot['end'];
            })->sort()->last();
    }
}
