<?php

namespace Kirby\Novelties\Models;

use BenSampo\Enum\Traits\CastsEnums;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Kirby\Company\Traits\HolidayAware;
use Kirby\Novelties\Enums\DayType;
use Kirby\Novelties\Enums\NoveltyTypeOperator;

/**
 * Class NoveltyType.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class NoveltyType extends Model
{
    use SoftDeletes;
    use CastsEnums;
    use HolidayAware;

    /**
     * @todo this constant flags should be configurable not hard coded.
     */
    public const DEFAULT_FOR_ADDITION = 'HADI';
    public const DEFAULT_FOR_SUBTRACTION = 'PP';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'name',
        'context_type',
        'time_zone',
        'apply_on_days_of_type',
        'apply_on_time_slots',
        'operator',
        'requires_comment',
        'keep_in_report',
    ];

    /**
     * The attributes that should be cast to enum types.
     *
     * @var array
     */
    protected $enumCasts = [
        'operator' => NoveltyTypeOperator::class,
        'apply_on_days_of_type' => DayType::class,
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'apply_on_time_slots' => 'array',
        'requires_comment' => 'bool',
        'keep_in_report' => 'bool',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    // ######################################################################## #
    // Relations
    // ######################################################################## #

    /**
     * Novelties relationship.
     */
    public function novelties()
    {
        return $this->hasMany(Novelty::class);
    }

    // ######################################################################## #
    // Methods
    // ######################################################################## #

    public function isForAddition(): bool
    {
        return $this->operator && $this->operator->is(NoveltyTypeOperator::Addition);
    }

    public function isDefaultForAddition(): bool
    {
        return $this->isForAddition() && self::DEFAULT_FOR_ADDITION === $this->code;
    }

    public function isForSubtraction(): bool
    {
        return $this->operator && $this->operator->is(NoveltyTypeOperator::Subtraction);
    }

    /**
     * @return mixed
     */
    public function canApplyOnDayType(DayType $dayType): bool
    {
        return $this->apply_on_days_of_type && $this->apply_on_days_of_type->is($dayType);
    }

    /**
     * Is this novelty applicable in any time?
     */
    public function isApplicableInAnyTime(): bool
    {
        return empty($this->apply_on_time_slots);
    }

    /**
     * Is this novelty applicable in any day?
     */
    public function isApplicableInAnyDay(): bool
    {
        return empty($this->apply_on_days_of_type);
    }

    public function isForWorkingTime(): bool
    {
        return 'normal_work_shift_time' === $this->context_type;
    }

    public function minStartTimeSlot(Carbon $relativeToTime = null): ?Carbon
    {
        $relativeToTime = $relativeToTime ?? now();
        $relativeToTime = $relativeToTime->copy();

        return collect($this->apply_on_time_slots)
            ->map(function (array $timeSlot) use ($relativeToTime) {
                $timeSlot = $this->mapTimeSlot($timeSlot, $relativeToTime);
                // @var Carbon
                $start = $timeSlot['start'];
                // @var Carbon
                $end = $timeSlot['end'];
                $fixTried = false;

                if (! $relativeToTime->between($start, $end) /*&& ! $start->isSameDay($end)*/) {
                    $start->addDay();
                    $end->addDay();
                    $fixTried = true;
                }

                $result = $start;
                $startIsHoliday = $this->isHoliday($start);
                $endIsHoliday = $this->isHoliday($end);

                if ($fixTried && ! $relativeToTime->between($start, $end)) {
                    return;
                }

                if ($this->canApplyOnDayType(DayType::Workday()) && $startIsHoliday && $endIsHoliday) {
                    return;
                }

                if ($this->canApplyOnDayType(DayType::Holiday()) && ! $startIsHoliday && ! $endIsHoliday) {
                    return;
                }

                if ($this->canApplyOnDayType(DayType::Workday()) && $startIsHoliday && ! $endIsHoliday) {
                    $result = $end->startOfDay();
                }

                if ($this->canApplyOnDayType(DayType::Workday()) && $startIsHoliday && ! $this->isHoliday($relativeToTime->copy()->setTimezone($this->time_zone))) {
                    $result = $relativeToTime->copy()->startOfDay();
                }

                if ($this->canApplyOnDayType(DayType::Holiday()) && ! $startIsHoliday && $endIsHoliday) {
                    $result = $start->addDay()->startOfDay();
                }

                return $result ? $result->setTimeZone('UTC') : null;
            })->filter()->sort()->first();
    }

    /**
     * @param  Carbon  $relativeToTime
     */
    public function maxEndTimeSlot(Carbon $relativeToTime = null): ?Carbon
    {
        $relativeToTime = $relativeToTime ?? now();

        return collect($this->apply_on_time_slots)
            ->map(function (array $timeSlot) use ($relativeToTime) {
                $timeSlot = $this->mapTimeSlot($timeSlot, $relativeToTime);
                // @var Carbon
                $start = $timeSlot['start'];
                // @var Carbon
                $end = $timeSlot['end'];
                $result = $end;
                $fixTried = false;

                if (! $relativeToTime->between($start, $end)) {
                    $start->addDay();
                    $end->addDay();
                    $fixTried = true;
                }

                if ($fixTried && ! $relativeToTime->between($start, $end)) {
                    return;
                }

                $startIsHoliday = $this->isHoliday($start);
                $endIsHoliday = $this->isHoliday($end);

                if ($this->canApplyOnDayType(DayType::Workday()) && $startIsHoliday && $endIsHoliday) {
                    return;
                }

                // remove holiday time if this novelty cant be applied on holidays
                if (! $this->canApplyOnDayType(DayType::Holiday()) && $endIsHoliday) {
                    $newEnd = $end->copy()->startOfDay()->subSecond();

                    $result = $this->isHoliday($newEnd)
                        ? null
                        : ($newEnd->between($start, $end) ? $newEnd : $this->maxEndTimeSlot($newEnd));
                }

                if ($this->canApplyOnDayType(DayType::Holiday()) && $startIsHoliday && ! $endIsHoliday) {
                    $result = $start->endOfDay()->setMilliseconds(0);
                }

                if ($this->canApplyOnDayType(DayType::Holiday()) && ! $startIsHoliday && ! $endIsHoliday) {
                    return;
                }

                return $result ? $result->setTimeZone('UTC') : null;
            })->filter()->sort()->last();
    }

    public function applicableTimeInMinutesFromTimeRange(Carbon $checkedInAt, Carbon $checkedOutAt): int
    {
        $applicableMinutes = 0;

        $startTime = $checkedInAt->between($this->minStartTimeSlot($checkedInAt), $this->maxEndTimeSlot($checkedInAt))
            ? $checkedInAt : $this->minStartTimeSlot($checkedInAt);

        $endTime = $checkedOutAt->between($this->minStartTimeSlot($checkedOutAt), $this->maxEndTimeSlot($checkedInAt))
            ? $checkedOutAt : $this->maxEndTimeSlot($checkedInAt);

        // fix for novelty types where their time slots are 21-06 like (from one day to another)
        if ($checkedOutAt->lessThan($this->minStartTimeSlot($checkedInAt))) {
            $endTime = $startTime;
        }

        $applicableMinutes = $startTime->diffInMinutes($endTime);

        if (! $checkedInAt->between($this->minStartTimeSlot($checkedInAt), $this->maxEndTimeSlot($checkedInAt), false)
            && ! $checkedOutAt->between($this->minStartTimeSlot($checkedOutAt), $this->maxEndTimeSlot($checkedOutAt), false)) {
            $applicableMinutes = 0;
        }

        if (empty($this->apply_on_time_slots)) {
            $applicableMinutes = $checkedInAt->diffInMinutes($checkedOutAt);
        }

        return $applicableMinutes;
    }

    /**
     * @return mixed
     */
    public function applicablePeriods(Carbon $start, Carbon $end): Collection
    {
        $result = [];
        $start = $start->copy()->setTimezone($this->time_zone);
        $end = $end->copy()->setTimezone($this->time_zone);

        if ($this->isApplicableInAnyTime()) {
            return collect([[$start->setTimezone('UTC'), $end->setTimezone('UTC')]]);
        }

        if (! $this->canApplyOnDayType(DayType::Holiday()) && $this->isHoliday($start) && $this->isHoliday($end)) {
            return collect([]);
        }

        if ($this->canApplyOnDayType(DayType::Holiday()) && ! $this->hasAnyHoliday([$start, $end])) {
            return collect([]);
        }

        if (! $start->isSameDay($end)) {
            return collect([
                [$this->minStartTimeSlot($start), $this->maxEndTimeSlot($start)],
                [$this->minStartTimeSlot($end), $this->maxEndTimeSlot($end)],
            ])
                ->map(fn ($range) => array_filter($range))
                ->filter(fn ($range) => 2 === count($range));
        }

        if ($start->isSameDay($end)) {
            $posibilites = [
                [$this->minStartTimeSlot($start), $this->maxEndTimeSlot($end)],
                [$this->minStartTimeSlot($start), $this->maxEndTimeSlot($start)],
                [$this->minStartTimeSlot($end), $this->maxEndTimeSlot($end)],
            ];

            $posibilites = array_values(array_filter($posibilites, fn ($period) => 2 === count(array_filter($period))));
            // remove duplicates
            $posibilites = array_reduce($posibilites, function (array $acc, array $possibility) {
                $valueExists = count(array_filter($acc, fn ($acc) => $acc[0]->equalTo($possibility[0]) && $acc[1]->equalTo($possibility[1]))) > 0;

                if (! $valueExists) {
                    $acc[] = $possibility;
                }

                return $acc;
            }, []);

            return collect($posibilites);
        }

        return collect([$result]);
    }

    /**
     * @param  Carbon  $date
     */
    private function mapTimeSlot(array $timeSlot, Carbon $relativeDate = null): array
    {
        $relativeDate = $relativeDate ?? now();
        $relativeDate = $relativeDate->copy();
        $relativeDate->setTimezone($this->time_zone);

        [$hour, $minutes, $seconds] = explode(':', $timeSlot['start']);
        $start = $relativeDate->copy()->setTime($hour, $minutes, $seconds);

        [$hour, $minutes, $seconds] = explode(':', $timeSlot['end']);
        $end = $relativeDate->copy()->setTime($hour, $minutes, $seconds);

        if ($start->greaterThan($end)) {
            $start = $start->subDay();
        }

        return [
            'end' => $end,
            'start' => $start,
        ];
    }
}
