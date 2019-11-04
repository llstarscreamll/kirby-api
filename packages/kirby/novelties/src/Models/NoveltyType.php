<?php

namespace Kirby\Novelties\Models;

use BenSampo\Enum\Traits\CastsEnums;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kirby\Novelties\Enums\DayType;
use Kirby\Novelties\Enums\NoveltyTypeOperator;

/**
 * Class NoveltyType.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class NoveltyType extends Model
{
    use SoftDeletes, CastsEnums;

    const DEFAULT_FOR_ADDITION = 'HADI';
    const DEFAULT_FOR_SUBTRACTION = 'PP';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'name',
        'context_type',
        'apply_on_days_of_type',
        'apply_on_time_slots',
        'operator',
        'requires_comment',
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
    ];
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    // ####################################################################### #
    //                                  Methods                                #
    // ####################################################################### #

    /**
     * @return bool
     */
    public function isDefaultForAddition(): bool
    {
        return $this->operator &&
        $this->operator->is(NoveltyTypeOperator::Addition) &&
        $this->code === self::DEFAULT_FOR_ADDITION;
    }

    /**
     * @return bool
     */
    public function isDefaultForSubtraction(): bool
    {
        return $this->operator &&
        $this->operator->is(NoveltyTypeOperator::Subtraction) &&
        $this->code === self::DEFAULT_FOR_SUBTRACTION;
    }

    /**
     * @param  DayType $dayType
     * @return mixed
     */
    public function canApplyOnDayType(DayType $dayType): bool
    {
        return $this->apply_on_days_of_type->is($dayType);
    }

    /**
     * Is this novelty applicable in any time?
     *
     * @return bool
     */
    public function isApplicableInAnyTime(): bool
    {
        return empty($this->apply_on_time_slots);
    }

    /**
     * Is this novelty applicable in any day?
     *
     * @return bool
     */
    public function isApplicableInAnyDay(): bool
    {
        return empty($this->apply_on_days_of_type);
    }

    /**
     * @param Carbon $relativeToTime
     */
    public function minStartTimeSlot(Carbon $relativeToTime = null)
    {
        $relativeToTime = $relativeToTime ?? now();

        return collect($this->apply_on_time_slots)
            ->map(function (array $timeSlot) use ($relativeToTime) {
                $timeSlot = $this->mapTimeSlot($timeSlot, $relativeToTime);

                return $timeSlot['start'];
            })->sort()->first();
    }

    /**
     * @param Carbon $relativeToTime
     */
    public function maxEndTimeSlot(Carbon $relativeToTime = null)
    {
        $relativeToTime = $relativeToTime ?? now();

        return collect($this->apply_on_time_slots)
            ->map(function (array $timeSlot) use ($relativeToTime) {
                $timeSlot = $this->mapTimeSlot($timeSlot, $relativeToTime);

                return $timeSlot['end'];
            })->sort()->last();
    }

    /**
     * @param array  $timeSlot
     * @param Carbon $date
     */
    private function mapTimeSlot(array $timeSlot, Carbon $date = null): array
    {
        $date = $date ?? now();
        [$hour, $seconds] = explode(':', $timeSlot['start']);
        $start = $date->copy()->setTime($hour, $seconds);
        [$hour, $seconds] = explode(':', $timeSlot['end']);
        $end = $date->copy()->setTime($hour, $seconds);
        if ($start->greaterThan($end)) {
            $end = $end->addDay();
        }

        return [
            'end' => $end,
            'start' => $start,
        ];
    }

    /**
     * @param  Carbon $checkedInAt
     * @param  Carbon $checkedOutAt
     * @return int
     */
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

        if (! $checkedInAt->between($this->minStartTimeSlot($checkedInAt), $this->maxEndTimeSlot($checkedInAt), false) &&
            ! $checkedOutAt->between($this->minStartTimeSlot($checkedOutAt), $this->maxEndTimeSlot($checkedOutAt), false)) {
            $applicableMinutes = 0;
        }

        if (empty($this->apply_on_time_slots)) {
            $applicableMinutes = $checkedInAt->diffInMinutes($checkedOutAt);
        }

        return $applicableMinutes;
    }
}
