<?php

namespace Kirby\WorkShifts\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

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
        'time_zone',
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
        'applies_on_days' => 'array',
    ];

    // ######################################################################## #
    // Accessors
    // ######################################################################## #

    /**
     * @todo return time in minutes, not hours
     *
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
    // Methods
    // ######################################################################## #

    public function canMealTimeApply(int $timeInMinutes): bool
    {
        return !empty($this->min_minutes_required_to_discount_meal_time)
        && $timeInMinutes >= $this->min_minutes_required_to_discount_meal_time;
    }

    public function startPunctuality(?Carbon $time = null, Carbon $offSet = null): int
    {
        return $this->slotPunctuality('start', $time ?? now(), $offSet, false);
    }

    public function endPunctuality(?Carbon $time = null, Carbon $offSet = null): int
    {
        return $this->slotPunctuality('end', $time ?? now(), $offSet);
    }

    public function matchingTimeSlot(string $flag, Carbon $time, ?Carbon $offSet = null, bool $beGraceTimeAware = false): ?array
    {
        return collect($this->time_slots)
            ->map(function ($timeSlot) use ($time, $flag, $offSet, $beGraceTimeAware) {
                return $this->mapTimeSlot($timeSlot, $time, $beGraceTimeAware, 'end' === $flag, $offSet);
            })
            ->sortBy(function (array $timeSlot) use ($time, $flag) {
                if ('end' === $flag && $time->diffInMinutes($timeSlot['end' === $flag ? 'start' : 'end']) < 60) {
                    return 100 * 100;
                }

                return $time->between($timeSlot['start'], $timeSlot['end']) ? 0 : $time->diffInMinutes($timeSlot[$flag]);
            })->first();
    }

    /**
     * @param string $flag   'start'|'end'
     * @param Carbon $offset
     *
     * @return null|int -1 early, zero on time, 1 late
     */
    public function slotPunctuality(string $flag, Carbon $time, ?Carbon $offSet = null, bool $beGraceTimeAware = false): ?int
    {
        $timeSlot = $this->matchingTimeSlot($flag, $time, $offSet, true);
        [$end, $start] = array_values($timeSlot);
        $targetTime = 'start' == $flag ? $start : $end;

        $flagGraceStart = $targetTime->copy();
        $flagGraceEnd = $targetTime->copy()->addSecond(); // second to fix end offsets

        if ('start' == $flag) {
            $flagGraceEnd = $flagGraceEnd->addMinutes($this->{"grace_minutes_before_{$flag}_times"} + $this->{"grace_minutes_after_{$flag}_times"});
        }

        if ('end' == $flag) {
            $flagGraceStart = $flagGraceStart->subMinutes($this->{"grace_minutes_before_{$flag}_times"} + $this->{"grace_minutes_after_{$flag}_times"});
        }

        if ($time->between($flagGraceStart, $flagGraceEnd)) {
            return 0;
        }

        return $time->lessThan($flagGraceStart) ? -1 : 1;
    }

    public function mappedTimeSlots(Carbon $date)
    {
        $beGraceTimeAware = false;

        return collect($this->time_slots)
            ->map(fn (array $timeSlot) => $this->mapTimeSlot($timeSlot, $date, $beGraceTimeAware))
            ->map(fn ($slot) => Arr::only($slot, ['start', 'end']))
            ->map(fn ($slot) => [$slot['start'], $slot['end']]);
    }

    /**
     * @param Carbon $offSet
     */
    public function getClosestSlotFlagTime(string $flag, Carbon $time, Carbon $offSet = null): ?Carbon
    {
        $timeSlot = $this->matchingTimeSlot($flag, $time, $offSet, $beGraceTimeAware = true);

        return $offSet ?? $timeSlot["original_{$flag}"] ?? null;
    }

    /**
     * @param Carbon $relativeToTime
     * @param bool   $beGraceTimeAware
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
     * @param null   $beGraceTimeAware
     */
    public function isMinStartTimeSlotInRage(Carbon $start, Carbon $end, Carbon $relativeToTime = null, $beGraceTimeAware = false): bool
    {
        return $this->minStartTimeSlot($relativeToTime, $beGraceTimeAware)->between($start, $end);
    }

    /**
     * @param bool $beGraceTimeAware
     * @param bool $relativeToEnd
     */
    public function maxEndTimeSlot(?Carbon $relativeToTime = null, $beGraceTimeAware = false, $relativeToEnd = true): ?Carbon
    {
        $relativeToTime = $relativeToTime ?? now();

        return collect($this->time_slots)
            ->map(function (array $timeSlot) use ($relativeToTime, $beGraceTimeAware, $relativeToEnd) {
                $timeSlot = $this->mapTimeSlot($timeSlot, $relativeToTime, $beGraceTimeAware, $relativeToEnd);

                return $timeSlot['end'];
            })->sort()->last();
    }

    /**
     * @param Carbon $relativeToTime
     * @param null   $beGraceTimeAware
     * @param false  $relativeToEnd
     */
    public function isMaxEndTimeSlotInRange(Carbon $start, Carbon $end, ?Carbon $relativeToTime = null, $beGraceTimeAware = false, $relativeToEnd = true): bool
    {
        return $this->maxEndTimeSlot($relativeToTime, $beGraceTimeAware, $relativeToEnd)->between($start, $end);
    }

    public function deadTimeRanges(?Carbon $relativeToTime = null): Collection
    {
        $relativeToTime = $relativeToTime ?? now();
        $slotsCount = count($this->time_slots ?? []);

        if ($slotsCount <= 1) {
            return collect([]);
        }

        $deadSlots = [];

        for ($i = 0; $i < $slotsCount; ++$i) {
            if (0 === $i) {
                $deadSlots[] = $this->time_slots[$i]['end'];

                continue;
            }

            if ($slotsCount === ($i + 1)) {
                $deadSlots[] = $this->time_slots[$i]['start'];

                continue;
            }

            $deadSlots[] = $this->time_slots[$i]['start'];
            $deadSlots[] = $this->time_slots[$i]['end'];
        }

        return collect($deadSlots)
            ->chunk(2)
            ->mapSpread(function ($even, $odd) use ($relativeToTime) {
                return $this->mapTimeSlot(
                    ['start' => $even, 'end' => $odd],
                    $relativeToTime,
                    false
                );
            });
    }

    public function hasDeadTimes(?Carbon $relativeToTime = null): bool
    {
        return $this->deadTimeRanges($relativeToTime)->count() > 0;
    }

    public function deadTimesSlotsFromTimeRange(Carbon $start, Carbon $end): Collection
    {
        return $this->deadTimeRanges($start)
            ->filter(function ($deadTimeSlot) use ($start, $end) {
                return $deadTimeSlot['start']->between($start, $end)
                && $deadTimeSlot['end']->between($start, $end);
            });
    }

    public function deadTimeInMinutesFromTimeRange(Carbon $start, Carbon $end): int
    {
        return $this->deadTimesSlotsFromTimeRange($start, $end)
            ->map(function ($deadTimeSlot) {
                return $deadTimeSlot['start']->diffInMinutes($deadTimeSlot['end']);
            })
            ->sum();
    }

    /**
     * @param Carbon $date
     * @param Carbon $offSet
     */
    private function mapTimeSlot(array $timeSlot, Carbon $date = null, bool $beGraceTimeAware = true, bool $relativeToEnd = false, Carbon $offSet = null): array
    {
        $date = $date ?? now();
        $date->setTimezone($this->time_zone);

        [$hour, $seconds] = explode(':', $timeSlot['start']);
        $start = $originalStart = $date->copy()->setTime($hour, $seconds);

        [$hour, $seconds] = explode(':', $timeSlot['end']);
        $end = $originalEnd = $date->copy()->setTime($hour, $seconds);

        if ($originalStart->greaterThan($originalEnd) && !$relativeToEnd) {
            $originalEnd = $originalEnd->addDay();
        }

        if ($beGraceTimeAware) {
            $start = $originalStart->copy()->subMinutes($this->grace_minutes_before_start_times);
            $end = $originalEnd->copy()->addMinutes($this->grace_minutes_after_end_times);
        }

        // set the time offset if needed
        if ($offSet) {
            $relativeToEnd ? $end = $offSet : $start = $offSet;
        }

        return ['end' => $end->setTimezone('UTC'), 'start' => $start->setTimezone('UTC'), 'original_start' => $originalStart->setTimezone('UTC'), 'original_end' => $originalEnd->setTimezone('UTC')];
    }
}
