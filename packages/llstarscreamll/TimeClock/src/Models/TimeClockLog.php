<?php

namespace llstarscreamll\TimeClock\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;
use llstarscreamll\Company\Contracts\HolidayRepositoryInterface;
use llstarscreamll\Company\Models\SubCostCenter;
use llstarscreamll\Employees\Models\Employee;
use llstarscreamll\Novelties\Enums\DayType;
use llstarscreamll\Novelties\Models\Novelty;
use llstarscreamll\Novelties\Models\NoveltyType;
use llstarscreamll\Users\Models\User;
use llstarscreamll\WorkShifts\Models\WorkShift;

/**
 * Class TimeClockLog.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class TimeClockLog extends Model
{
    use SoftDeletes;

    /**
     * @var HolidayRepositoryInterface
     */
    private $holidayRepository;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'employee_id',
        'sub_cost_center_id',
        'work_shift_id',
        'checked_in_at',
        'expected_check_in_at',
        'check_in_novelty_type_id',
        'check_in_sub_cost_center_id',
        'checked_out_at',
        'expected_check_out_at',
        'check_out_novelty_type_id',
        'check_out_sub_cost_center_id',
        'checked_in_by_id',
        'checked_out_by_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'employee_id' => 'int',
        'work_shift_id' => 'int',
        'check_in_novelty_type_id' => 'int',
        'check_out_novelty_type_id' => 'int',
        'checked_in_by_id' => 'int',
        'checked_out_by_id' => 'int',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'checked_in_at',
        'checked_out_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // ####################################################################### #
    //                                 Relations                               #
    // ####################################################################### #

    /**
     * @return mixed
     */
    public function workShift()
    {
        return $this->belongsTo(WorkShift::class);
    }

    /**
     * @return mixed
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    /**
     * @return mixed
     */
    public function checkInNovelty()
    {
        return $this->belongsTo(NoveltyType::class, 'check_in_novelty_type_id');
    }

    /**
     * @return mixed
     */
    public function checkOutNovelty()
    {
        return $this->belongsTo(NoveltyType::class, 'check_out_novelty_type_id');
    }

    /**
     * @return mixed
     */
    public function novelties()
    {
        return $this->hasMany(Novelty::class);
    }

    /**
     * @return mixed
     */
    public function subCostCenter()
    {
        return $this->belongsTo(SubCostCenter::class)->withTrashed();
    }

    /**
     * @return mixed
     */
    public function checkInSubCostCenter()
    {
        return $this->belongsTo(SubCostCenter::class)->withTrashed();
    }

    /**
     * @return mixed
     */
    public function checkOutSubCostCenter()
    {
        return $this->belongsTo(SubCostCenter::class)->withTrashed();
    }

    /**
     * @return mixed
     */
    public function approvals()
    {
        return $this->belongsToMany(User::class, 'time_clock_log_approvals')->withTimestamps()->withTrashed();
    }

    // ####################################################################### #
    //                                 Accessors                               #
    // ####################################################################### #

    /**
     * @return float
     */
    public function getClockedMinutesAttribute(): float
    {
        return $this->checked_in_at->diffInMinutes($this->checked_out_at);
    }

    // ####################################################################### #
    //                                  Methods                                #
    // ####################################################################### #

    /**
     * @return HolidayRepositoryInterface
     */
    private function holidayRepository(): HolidayRepositoryInterface
    {
        if (! $this->holidayRepository) {
            $this->holidayRepository = App::make(HolidayRepositoryInterface::class);
        }

        return $this->holidayRepository;
    }

    /**
     * @return bool
     */
    public function hasWorkShift(): bool
    {
        return (bool) $this->work_shift_id;
    }

    /**
     * @return mixed
     */
    public function checkedInOnHoliday(): bool
    {
        $holidaysCount = $this->holidayRepository()->countWhereIn('date', [$this->checked_in_at->toDateString()]);

        return $holidaysCount || $this->checked_in_at->isSunday();
    }

    /**
     * @return bool
     */
    public function checkedOutOnHoliday(): bool
    {
        $holidaysCount = $this->holidayRepository()->countWhereIn('date', [$this->checked_out_at->toDateString()]);

        return $holidaysCount || $this->checked_out_at->isSunday();
    }

    /**
     * Was checked_in_at or checked_out_at made on sunday?
     *
     * @return bool
     */
    public function hasHolidaysChecks(): bool
    {
        return $this->checkedInOnHoliday() || $this->checkedOutOnHoliday();
    }

    /**
     * @param DayType $dayType
     */
    public function getClockedTimeMinutesByDayType(DayType $dayType)
    {
        $times = [];
        $timeInMinutes = 0;
        $isTheSameDay = $this->checked_in_at->isSameDay($this->checked_out_at);

        // not the same day
        if ($dayType->is(DayType::Holiday) && $this->checkedInOnHoliday() && ! $this->checkedOutOnHoliday() && ! $isTheSameDay) {
            $timeInMinutes += $this->checked_in_at->diffInMinutes($this->checked_out_at->startOfDay());
            $times = [$this->checked_in_at, $this->checked_out_at->startOfDay()];
        }

        if ($dayType->is(DayType::Holiday) && ! $this->checkedInOnHoliday() && $this->checkedOutOnHoliday() && ! $isTheSameDay) {
            $timeInMinutes += $this->checked_in_at->endOfDay()->diffInMinutes($this->checked_out_at);
            $times = [$this->checked_in_at->endOfDay(), $this->checked_out_at];
        }

        if ($dayType->is(DayType::Workday) && $this->checkedInOnHoliday() && ! $this->checkedOutOnHoliday() && ! $isTheSameDay) {
            $timeInMinutes += $this->checked_in_at->endOfDay()->diffInMinutes($this->checked_out_at);
            $times = [$this->checked_in_at->endOfDay(), $this->checked_out_at];
        }

        if ($dayType->is(DayType::Workday) && ! $this->checkedInOnHoliday() && $this->checkedOutOnHoliday() && ! $isTheSameDay) {
            $timeInMinutes += $this->checked_in_at->diffInMinutes($this->checked_out_at->startOfDay());
            $times = [$this->checked_in_at, $this->checked_out_at->startOfDay()];
        }

        if ($dayType->is(DayType::Workday) && ! $this->hasHolidaysChecks() && ! $isTheSameDay) {
            $timeInMinutes += $this->clocked_minutes;
        }

        if ($dayType->is(DayType::Holiday) && $this->checkedInOnHoliday() && $this->checkedOutOnHoliday() && ! $isTheSameDay) {
            $timeInMinutes += $this->clocked_minutes;
        }

        // same day
        if ($dayType->is(DayType::Holiday) && $this->checkedInOnHoliday() && $isTheSameDay) {
            $timeInMinutes = $this->clocked_minutes;
            $times = [$this->checked_in_at, $this->checked_out_at];
        }

        if ($dayType->is(DayType::Workday) && ! $this->checkedInOnHoliday() && $isTheSameDay) {
            $timeInMinutes = $this->clocked_minutes;
            $times = [$this->checked_in_at, $this->checked_out_at];
        }

        return [$timeInMinutes, $times];
    }

    /**
     * Get related sub cost centers to this time clock log.
     *
     * @return array
     */
    public function relatedSubCostCenters(): array
    {
        return array_filter([
            $this->subCostCenter,
            $this->checkInSubCostCenter,
            $this->checkOutSubCostCenter,
        ]);
    }

    /**
     * @return bool
     */
    public function requireSubCostCenter(Carbon $endTime): bool
    {
        return ($this->check_in_novelty_type_id && ! $this->check_in_sub_cost_center_id)
            || (! $this->sub_cost_center_id && ! $this->check_in_sub_cost_center_id && ! $this->check_out_sub_cost_center_id)
            || ($this->work_shift_id && ! $this->sub_cost_center_id && $this->workShift && $endTime->greaterThan($this->workShift->minStartTimeSlot($endTime)))
            || ($this->workShift && $endTime->greaterThan($this->workShift->minStartTimeSlot($endTime)));
    }

    /**
     * @return bool
     */
    public function hasClockedTimeOnWorkShift(): bool
    {
        $hasClockedTimeOnWorkShift = true;

        if ($this->work_shift_id) {
            $beGraceTimeAware = true;
            $workShiftStart = $this->workShift->minStartTimeSlot($this->checked_in_at, $beGraceTimeAware);
            $workShiftEnd = $this->workShift->maxEndTimeSlot($this->checked_out_at, $beGraceTimeAware);

            $hasClockedTimeOnWorkShift = $this->checked_in_at->between($workShiftStart, $workShiftEnd)
            || $this->checked_out_at->between($workShiftStart, $workShiftEnd)
                || ($this->checked_in_at->lessThan($workShiftStart) && $this->checked_out_at->greaterThan($workShiftEnd));
        }

        return $hasClockedTimeOnWorkShift;
    }

    public function checkInPunctuality(): ?int
    {
        return optional($this->workShift)->startPunctuality($this->checked_in_at, true);
    }

    public function checkOutPunctuality(): ?int
    {
        return optional($this->workShift)->endPunctuality($this->checked_out_at, true);
    }
}
