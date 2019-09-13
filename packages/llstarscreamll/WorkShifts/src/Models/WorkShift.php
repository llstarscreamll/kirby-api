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
    public function startPunctuality(Carbon $time = null, $debug = false): int
    {
        return $this->slotPunctuality('start', $time ?? now(), null, false, $debug);
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
     * @return mixed
     */
    private function foo(string $flag, Carbon $time, ?Carbon $offSet = null, bool $beGraceTimeAware = false, $debug = false)
    {
        $foo = collect($this->time_slots)
            ->map(function ($timeSlot) use ($time, $flag, $offSet, $beGraceTimeAware) {
                return $this->mapTimeSlot($timeSlot, $time, $beGraceTimeAware, $flag === 'end', $offSet);
            })
            ->sortBy(function (array $timeSlot) use ($time, $flag) {
                return $time->between($timeSlot['start'], $timeSlot['end'])
                    ? 0 : $time->diffInMinutes($timeSlot[$flag]);
            })->first();

        return collect($this->time_slots)
            ->map(function ($timeSlot) use ($time, $flag, $offSet, $beGraceTimeAware) {
                return $this->mapTimeSlot($timeSlot, $time, $beGraceTimeAware, $flag === 'end', $offSet);
            })
            ->sortBy(function (array $timeSlot) use ($time, $flag) {
                return $time->between($timeSlot['start'], $timeSlot['end']) ? 0 : $time->diffInMinutes($timeSlot[$flag]);
            })->first();
    }

    /**
     * @param  string $flag     'start'|'end'
     * @param  Carbon $time
     * @param  Carbon $offset
     * @return int    -1 early, zero on time, 1 late
     */
    public function slotPunctuality(string $flag, Carbon $time, ?Carbon $offSet = null, bool $beGraceTimeAware = false, $debug = true): ?int
    {
        $timeSlot = $this->foo($flag, $time, $offSet, true);
        [$end, $start] = array_values($timeSlot);
        $targetTime = $flag == 'start' ? $start : $end;

        $flagGraceStart = $targetTime->copy();
        $flagGraceEnd = $targetTime->copy();

        if ($flag == 'start') {
            $flagGraceEnd = $flagGraceEnd->addMinutes($this->{"grace_minutes_before_{$flag}_times"}+$this->{"grace_minutes_after_{$flag}_times"});
        }

        if ($flag == 'end') {
            $flagGraceStart = $flagGraceStart->subMinutes($this->{"grace_minutes_before_{$flag}_times"}+$this->{"grace_minutes_after_{$flag}_times"});
        }

        if ($time->between($flagGraceStart, $flagGraceEnd)) {
            return 0;
        }

        return $time->lessThan($flagGraceStart) ? -1 : 1;
    }

    /**
     * @param array  $timeSlot
     * @param Carbon $date
     * @param bool   $beGraceTimeAware
     * @param bool   $relativeToEnd
     * @param Carbon $offSet
     */
    private function mapTimeSlot(array $timeSlot, Carbon $date = null, bool $beGraceTimeAware = true, bool $relativeToEnd = false, Carbon $offSet = null): array
    {
        $date = $date ?? now();

        [$hour, $seconds] = explode(':', $timeSlot['start']);
        $start = $originalStart = $date->copy()->setTime($hour, $seconds);

        [$hour, $seconds] = explode(':', $timeSlot['end']);
        $end = $originalEnd = $date->copy()->setTime($hour, $seconds);

        if ($originalStart->greaterThan($originalEnd) && ! $relativeToEnd) {
            $originalEnd = $originalEnd->addDay();
        }

        if ($relativeToEnd) {
            $originalStart = $originalStart->subDay();
        }

        if ($beGraceTimeAware) {
            $start = $originalStart->copy()->subMinutes($this->grace_minutes_before_start_times);
            $end = $originalEnd->copy()->addMinutes($this->grace_minutes_after_end_times);
        }

        // set the time offset if needed
        if ($offSet) {
            $relativeToEnd ? $end = $offSet : $start = $offSet;
        }

        return ['end' => $end, 'start' => $start, 'original_start' => $originalStart, 'original_end' => $originalEnd];
    }

    /**
     * @param  string  $flag
     * @param  Carbon  $time
     * @param  Carbon  $offSet
     * @return mixed
     */
    public function getClosestSlotFlagTime(string $flag, Carbon $time, Carbon $offSet = null, $debug = false): ?Carbon
    {
        $timeSlot = $this->foo($flag, $time, $offSet, true, true);

        return $offSet ?? $timeSlot["original_{$flag}"] ?? null;
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
