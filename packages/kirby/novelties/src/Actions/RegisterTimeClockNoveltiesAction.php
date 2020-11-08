<?php

namespace Kirby\Novelties\Actions;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Kirby\Novelties\Contracts\NoveltyRepositoryInterface;
use Kirby\Novelties\Contracts\NoveltyTypeRepositoryInterface;
use Kirby\Novelties\Enums\NoveltyTypeOperator;
use Kirby\Novelties\Models\Novelty;
use Kirby\Novelties\Models\NoveltyType;
use Kirby\TimeClock\Contracts\TimeClockLogRepositoryInterface;
use Kirby\TimeClock\Models\TimeClockLog;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use Spatie\Period\Precision;

/**
 * Class RegisterTimeClockNoveltiesAction.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class RegisterTimeClockNoveltiesAction
{
    /**
     * @var NoveltyRepositoryInterface
     */
    private $noveltyRepository;

    /**
     * @var NoveltyTypeRepositoryInterface
     */
    private $noveltyTypeRepository;

    /**
     * @var TimeClockLogRepositoryInterface
     */
    private $timeClockLogRepository;

    /**
     * @var Collection
     */
    private $scheduledNovelties;

    /**
     * @var array
     */
    private $takenPeriods = [];

    /**
     * @param NoveltyRepositoryInterface      $noveltyRepository
     * @param NoveltyTypeRepositoryInterface  $noveltyTypeRepository
     * @param TimeClockLogRepositoryInterface $timeClockLogRepository
     */
    public function __construct(
        NoveltyRepositoryInterface $noveltyRepository,
        NoveltyTypeRepositoryInterface $noveltyTypeRepository,
        TimeClockLogRepositoryInterface $timeClockLogRepository
    ) {
        $this->noveltyRepository = $noveltyRepository;
        $this->noveltyTypeRepository = $noveltyTypeRepository;
        $this->timeClockLogRepository = $timeClockLogRepository;
    }

    /**
     * @param  int    $timeClockLogId
     * @return bool
     */
    public function run(int $timeClockLogId): bool
    {
        $currentDate = now();
        $timeClockLog = $this->timeClockLogRepository->with([
            'workShift', 'checkInNovelty', 'checkOutNovelty', 'novelties',
        ])->find($timeClockLogId);

        if (! $this->noveltiesCanBeCalculated($timeClockLog)) {
            return false;
        }

        $this->attachScheduledNovelties($timeClockLog);

        $novelties = $this->noveltyTypeRepository
            ->all()
            ->sort(fn (NoveltyType $novelty) => $novelty->isDefaultForSubtraction() ? 9999 : 0)
            ->map(function ($noveltyType) use ($timeClockLog, $currentDate) {
                $periods = $this->solveNoveltyTypeTime($timeClockLog, $noveltyType);
                $subCostCenterId = $timeClockLog->sub_cost_center_id;

                if ($noveltyType->id === $timeClockLog->check_in_novelty_type_id && $timeClockLog->check_in_sub_cost_center_id) {
                    $subCostCenterId = $timeClockLog->check_in_sub_cost_center_id;
                }

                if ($noveltyType->id === $timeClockLog->check_out_novelty_type_id && $timeClockLog->check_out_sub_cost_center_id) {
                    $subCostCenterId = $timeClockLog->check_out_sub_cost_center_id;
                }

                $operator = $noveltyType->operator->is(NoveltyTypeOperator::Subtraction()) ? -1 : 1;

                return array_map(fn (array $period) => [
                    'code' => $noveltyType->code,
                    'time_clock_log_id' => $timeClockLog->id,
                    'employee_id' => $timeClockLog->employee_id,
                    'novelty_type_id' => $noveltyType->id,
                    'sub_cost_center_id' => $subCostCenterId,
                    'start_at' => $period[0]->format('Y-m-d H:i:s'),
                    'end_at' => $period[1]->format('Y-m-d H:i:s'),
                    'total_time_in_minutes' => (int) (($period[1]->getTimestamp() - $period[0]->getTimestamp()) / 60) * $operator,
                    'created_at' => $currentDate->toDateTimeString(),
                    'updated_at' => $currentDate->toDateTimeString(),
                ], $periods);
            })
            ->filter()
            ->collapse()
            // ->dd()
            ->filter(fn ($novelty) => ! empty($novelty['total_time_in_minutes']))
            ->map(fn ($i) => Arr::except($i, ['code', 'total_time_in_minutes']));

        $this->noveltyRepository->insert($novelties->all());

        return true;
    }

    /**
     * @param  TimeClockLog $timeClockLog
     * @return bool
     */
    private function noveltiesCanBeCalculated(TimeClockLog $timeClockLog): bool
    {
        $validations = [
            ! empty($timeClockLog->checked_out_at),
            $timeClockLog->checked_out_at && $timeClockLog->checked_in_at->diffInMinutes($timeClockLog->checked_out_at) > 2,
        ];

        return ! in_array(false, $validations);
    }

    /**
     * @param  TimeClockLog $timeClockLog
     * @param  NoveltyType  $noveltyType
     * @return array
     */
    private function solveNoveltyTypeTime(TimeClockLog $timeClockLog, NoveltyType $noveltyType): array
    {
        $this->novelType = $noveltyType;
        $result = new PeriodCollection();
        $mealPeriod = $this->getMealPeriod($timeClockLog);
        $logPeriod = $this->getTimeClockPeriod($timeClockLog);
        $workShiftPeriods = $this->getWorkShiftPeriods($timeClockLog);
        $scheduledNoveltiesPeriods = $this->scheduledNoveltiesPeriods($timeClockLog);
        $noveltyTypePeriods = $this->getNoveltyTypePeriods($timeClockLog, $noveltyType);
        $logPeriodsOutOfWorkShift = $this->getLogPeriodWithoutWorkShiftTime($logPeriod, $workShiftPeriods);
        $logOverlapWithWorkShiftSlotsPeriods = $workShiftPeriods->filter(fn (Period $wp) => $wp->overlapsWith($logPeriod));

        $noveltySelectedInCheckIn = $timeClockLog->check_in_novelty_type_id === $noveltyType->id;
        $noveltySelectedInCheckOut = $timeClockLog->check_out_novelty_type_id === $noveltyType->id;
        $noveltySelectedByEmployee = $noveltySelectedInCheckIn || $noveltySelectedInCheckOut;

        if (! $noveltyTypePeriods->count()) {
            return [];
        }

        if ($noveltyType->isForWorkingTime()) {
            $result = $workShiftPeriods
                ->overlap(new PeriodCollection($logPeriod))
                ->overlap($noveltyTypePeriods);
        }

        if ($noveltySelectedByEmployee && ($timeClockLog->onTimeCheckIn() && $timeClockLog->lateCheckOut())) {
            $result = $logPeriodsOutOfWorkShift->overlap($noveltyTypePeriods);
        }

        if ($noveltySelectedByEmployee && ($timeClockLog->earlyCheckIn() || $timeClockLog->lateCheckOut())) {
            $result = $logPeriodsOutOfWorkShift->overlap($noveltyTypePeriods);
        }

        if ($noveltySelectedByEmployee && ($timeClockLog->onTimeCheckIn() && $timeClockLog->earlyCheckOut())) {
            $result = $workShiftPeriods->overlap($noveltyTypePeriods);
        }

        if ($noveltySelectedByEmployee && ($timeClockLog->lateCheckIn() && $timeClockLog->onTimeCheckOut())) {
            $result = $workShiftPeriods->overlap($noveltyTypePeriods);
        }

        if ($noveltySelectedByEmployee && ($timeClockLog->earlyCheckIn() && $timeClockLog->onTimeCheckOut())) {
            $result = $logPeriodsOutOfWorkShift->overlap($noveltyTypePeriods);
        }
        if ($noveltySelectedByEmployee && ($timeClockLog->lateCheckIn() && $timeClockLog->lateCheckOut())) {
            $result = $logPeriodsOutOfWorkShift->overlap($noveltyTypePeriods);
        }

        // novelty type selected in late checkin
        if ($timeClockLog->lateCheckIn() && $noveltySelectedInCheckIn) {
            $result = new PeriodCollection(
                ...collect([...$noveltyTypePeriods])
                    ->map(fn (Period $n) => [...$n->diff($logPeriod)])
                    ->collapse()
            );
        }

        // novelty selected in early checkout
        if ($timeClockLog->earlyCheckout() && $noveltySelectedInCheckOut) {
            $result = new PeriodCollection(
                ...collect([...$noveltyTypePeriods])
                    ->map(fn (Period $noveltyPeriod) => [...$noveltyPeriod->diff($logPeriod)])
                    ->collapse()
            );
        }

        if (! $noveltySelectedByEmployee && $noveltyType->isDefaultForAdditionOrSubtraction()) {
            $result = $logPeriodsOutOfWorkShift->overlap($noveltyTypePeriods);
        }

        if (! $noveltySelectedByEmployee && ($timeClockLog->lateCheckIn() || $timeClockLog->earlyCheckOut()) && $noveltyType->isDefaultForSubtraction()) {
            $missingWorkShiftTime = collect([...$logOverlapWithWorkShiftSlotsPeriods])
                ->map(fn (Period $wp) => [...$wp->diff(...$scheduledNoveltiesPeriods)])
                ->collapse()
                ->map(fn (Period $wp) => [...$wp->diff($logPeriod)])
                ->collapse();

            $result = $noveltyTypePeriods->overlap(new PeriodCollection(...$missingWorkShiftTime));
        }

        if ($noveltySelectedByEmployee && ! $timeClockLog->hasWorkShift()) {
            $result = $logPeriodsOutOfWorkShift->overlap($noveltyTypePeriods);
        }

        // remove meal time when novelty type is for working time
        if (! $result->isEmpty() && ! empty($mealPeriod) && $noveltyType->isForWorkingTime()) {
            $result = $result->overlap($result[0]->diff($mealPeriod));
        }

        if ($noveltySelectedByEmployee && $logOverlapWithWorkShiftSlotsPeriods->isEmpty() && ! $logPeriodsOutOfWorkShift->isEmpty() && $noveltyType->isForAddition()) {
            $result = new PeriodCollection($logPeriod);
        }

        if ($noveltySelectedByEmployee && $logOverlapWithWorkShiftSlotsPeriods->isEmpty() && ! $logPeriodsOutOfWorkShift->isEmpty() && $noveltyType->isForSubtraction()) {
            $result = $workShiftPeriods;
        }

        $result = $this->subtractTimeAlreadyTaken($result, $noveltyType);

        return $this->takenPeriods[$noveltyType->code] = $result->reduce(function ($a, $b) {
            $a[] = [$b->getStart(), $b->getEnd()];

            return $a;
        }, []);
    }

    /**
     * @param  Period           $timeClockPeriod
     * @param  PeriodCollection $workShiftPeriods
     * @return mixed
     */
    private function getLogPeriodWithoutWorkShiftTime(Period $timeClockPeriod, PeriodCollection $workShiftPeriods): PeriodCollection
    {
        $burnedWorkShiftTime = $timeClockPeriod->overlap(...$workShiftPeriods);
        $base = $timeClockPeriod->diff(...$burnedWorkShiftTime);

        return new PeriodCollection(...$base);
    }

    /**
     * @param  PeriodCollection $noveltyTypePeriods
     * @return mixed
     */
    private function subtractTimeAlreadyTaken(PeriodCollection $noveltyTypePeriods, $novelty): PeriodCollection
    {
        if ($noveltyTypePeriods->isEmpty()) {
            return $noveltyTypePeriods;
        }

        $takenOverlaps = collect($this->takenPeriods)
            ->filter()
            ->filter(fn ($periods) => array_filter($periods, fn ($period) => $period[0]->getTimestamp() - $period[1]->getTimestamp() !== 0))
            ->map(fn ($periods) => array_map(fn ($period) => [...$period, Precision::SECOND], $periods))
            ->map(fn ($periods) => array_map(fn ($period) => new Period(...$period), $periods))
            ->collapse()
            ->filter(fn (Period $period) => $period->overlapsWith(...$noveltyTypePeriods));

        if ($takenOverlaps->count()) {
            $takenOverlapsPeriods = new PeriodCollection(...$takenOverlaps);

            return new PeriodCollection(
                ...collect([...$noveltyTypePeriods->filter(fn (Period $np) => $np->overlap(...$takenOverlapsPeriods))])
                    ->map(fn (Period $np) => [...$np->diff(...$takenOverlapsPeriods)])
                    ->collapse()
            );

            return new PeriodCollection(
                ...$takenOverlaps
                    ->map(fn (Period $period) => $period->diff(...$noveltyTypePeriods))
                    ->map(fn (PeriodCollection $periods) => [...$periods])
                    ->collapse()
            );
        }

        return $noveltyTypePeriods;
    }

    /**
     * @param  TimeClockLog       $timeClockLog
     * @return PeriodCollection
     */
    private function getWorkShiftPeriods(TimeClockLog $timeClockLog): PeriodCollection
    {
        $shiftTimeSlots = $timeClockLog->hasWorkShift()
            ? $timeClockLog->workShift->mappedTimeSlots($timeClockLog->checked_in_at)
            : collect([]);

        $workShiftPeriods = $shiftTimeSlots
            ->map(fn ($slot) => [...$slot, Precision::SECOND])
            ->map(fn ($slot) => Period::make(...$slot));

        return new PeriodCollection(...$workShiftPeriods);
    }

    /**
     * @param $timeClockLog
     */
    private function getTimeClockPeriod($timeClockLog): Period
    {
        return Period::make($timeClockLog->softCheckInAt(), $timeClockLog->softCheckOutAt(), Precision::SECOND);
    }

    /**
     * @param $noveltyType
     */
    private function getNoveltyTypePeriods(TimeClockLog $timeClockLog, NoveltyType $noveltyType)
    {
        $basePeriodForNovelty = collect([$this->solveBaseTimeForNovelty($timeClockLog, $noveltyType)]);
        $scheduledNoveltiesPeriods = $this->scheduledNoveltiesPeriods($timeClockLog);

        if (! $scheduledNoveltiesPeriods->isEmpty()) {
            $basePeriodForNoveltyX = Period::make(...[...$basePeriodForNovelty[0], Precision::SECOND])
                ->diff(...$scheduledNoveltiesPeriods);

            if ($basePeriodForNoveltyX->count()) {
                $basePeriodForNovelty = collect([...$basePeriodForNoveltyX])
                    ->map(fn (Period $period) => [$period->getStart(), $period->getEnd()]);
            }
        }

        $noveltyTypePeriods =
        collect([...$basePeriodForNovelty])
            ->map(
                fn (array $base) => $noveltyType
                    ->applicablePeriods(Carbon::instance($base[0]), Carbon::instance($base[1]))
                    ->map(fn ($i) => array_filter($i))
                    ->filter()
            )
            ->collapse()
            ->filter();

        // caso en el que no hay turno ni novedades
        if (! $timeClockLog->hasWorkShift() &&
            (
                $timeClockLog->check_in_novelty_type_id === $noveltyType->id ||
                // fix check_in_novelty_type_id on second comparison. should be check_out_novelty_type_id
                (empty($timeClockLog->check_in_novelty_type_id) && $noveltyType->isDefaultForAddition())
            )
        ) {
            $noveltyTypePeriods = collect([...$basePeriodForNovelty]);
        }

        $noveltyTypePeriods = collect($noveltyTypePeriods)
            ->map(fn ($slot) => [...$slot, Precision::SECOND])
            ->map(fn ($slot) => Period::make(...$slot));

        return (new PeriodCollection(...$noveltyTypePeriods))
            ->overlap(
                new PeriodCollection(
                    ...$basePeriodForNovelty->map(fn ($b) => Period::make(...[...$b, Precision::SECOND]))
                )
            );
    }

    /**
     * @param $timeClockLog
     */
    private function scheduledNoveltiesPeriods($timeClockLog): PeriodCollection
    {
        $scheduledEndNoveltyPeriod = $this->getTimeFlagOffSetBasedOnScheduledNovelty('end', $timeClockLog);
        $scheduledStartNoveltyPeriod = $this->getTimeFlagOffSetBasedOnScheduledNovelty('start', $timeClockLog);

        return new PeriodCollection(...array_filter([$scheduledEndNoveltyPeriod, $scheduledStartNoveltyPeriod]));
    }

    /**
     * @param  TimeClockLog  $timeClockLog
     * @return Period|null
     */
    private function getMealPeriod(TimeClockLog $timeClockLog): ?Period
    {
        if (! $timeClockLog->hasWorkShift()) {
            return null;
        }

        $clockTimeStart = $timeClockLog->checked_in_at;
        $softCheckIn = $timeClockLog->softCheckInAt();
        $softCheckOut = $timeClockLog->softCheckOutAt();
        $mealMinutes = $timeClockLog->workShift->meal_time_in_minutes;

        if (! $timeClockLog->workShift->canMealTimeApply($softCheckOut->diffInMinutes($softCheckIn))) {
            return null;
        }

        $workShiftPeriods = $timeClockLog->workShift->mappedTimeSlots($clockTimeStart);

        $workShiftSlot = collect($workShiftPeriods)
            ->sortByDesc(fn ($slot) => $slot[0]->diffInMinutes($slot[1]))
            ->first();

        [$shiftStart, $shiftEnd] = $workShiftSlot;
        $workShiftHalfMinutesElapsed = ($shiftStart->diffInMinutes($shiftEnd) / 2) - ($mealMinutes / 2);

        return Period::make(
            $shiftStart->copy()->addMinutes($workShiftHalfMinutesElapsed),
            $shiftStart->copy()->addMinutes($workShiftHalfMinutesElapsed + $mealMinutes),
            Precision::SECOND
        );
    }

    /**
     * @param  TimeClockLog $timeClockLog
     * @param  NoveltyType  $noveltyType
     * @return array
     */
    private function solveBaseTimeForNovelty(TimeClockLog $timeClockLog, NoveltyType $noveltyType): array
    {
        $start = $timeClockLog->expectedCheckIn();
        $end = $timeClockLog->expectedCheckOut();
        $noveltySelectedInCheckIn = $noveltyType->id === $timeClockLog->check_in_novelty_type_id;
        $noveltySelectedInCheckOut = $noveltyType->id === $timeClockLog->check_out_novelty_type_id;
        $noveltySelectedByEmployee = $noveltySelectedInCheckIn || $noveltySelectedInCheckOut;

        if (! $timeClockLog->hasWorkShift()) {
            return [$timeClockLog->checked_in_at, $timeClockLog->checked_out_at];
        }

        if (! $timeClockLog->onTimeCheckIn() || ! $timeClockLog->onTimeCheckOut()) {
            $start = $timeClockLog->checked_in_at;
            $end = $timeClockLog->checked_out_at;
        }

        if ($noveltySelectedInCheckIn && ! $noveltySelectedInCheckOut) {
            $end = $timeClockLog->expectedCheckOut();
        }

        if ($noveltySelectedInCheckOut && ! $noveltySelectedInCheckIn) {
            $start = $timeClockLog->expectedCheckIn();
        }

        if (($timeClockLog->lateCheckIn() || $timeClockLog->earlyCheckout()) && $noveltyType->isForSubtraction()) {
            $start = $timeClockLog->expectedCheckIn();
            $end = $timeClockLog->expectedCheckOut();
        }

        if ($noveltySelectedByEmployee && $noveltyType->isDefaultForAddition()) {
            $end = $timeClockLog->checked_out_at;
        }

        return [$start->setTimezone('UTC'), $end->setTimezone('UTC')];
    }

    /**
     * @param  TimeClockLog $timeClockLog
     * @return int
     */
    private function attachScheduledNovelties(TimeClockLog $timeClockLog): int
    {
        $scheduledNoveltiesIds = $this->scheduledNovelties($timeClockLog)
            ->filter(function (Novelty $novelty) {
                return empty($novelty->time_clock_log_id);
            })
            ->pluck('id')
            ->all();

        return $this->noveltyRepository->updateWhereIn('id', $scheduledNoveltiesIds, [
            'time_clock_log_id' => $timeClockLog->id,
            'sub_cost_center_id' => $timeClockLog->sub_cost_center_id,
        ]);
    }

    /**
     * @param  TimeClockLog $timeClockLog
     * @return Collection
     */
    private function scheduledNovelties(TimeClockLog $timeClockLog): Collection
    {
        if ($this->scheduledNovelties) {
            return $this->scheduledNovelties;
        }

        // scheduled novelties should not exists if work shift is empty
        if (! $timeClockLog->hasWorkShift()) {
            return $this->scheduledNovelties = collect([]);
        }

        $beGraceTimeAware = true;
        $start = $timeClockLog->workShift->minStartTimeSlot($timeClockLog->checked_in_at, $beGraceTimeAware);
        $end = $timeClockLog->workShift->maxEndTimeSlot($timeClockLog->checked_out_at, $beGraceTimeAware);

        $this->scheduledNovelties = $this->noveltyRepository
            ->whereScheduledForEmployee($timeClockLog->employee_id, 'start_at', $start, $end)
            ->get(['novelties.*']);

        return $this->scheduledNovelties;
    }

    /**
     * @param  TimeClockLog  $timeClockLog
     * @return null|Period
     */
    private function getTimeFlagOffSetBasedOnScheduledNovelty(string $flag, TimeClockLog $timeClockLog): ?Period
    {
        $logAction = $flag === 'start' ? 'checked_in_at' : 'checked_out_at';
        $comparison = $flag === 'start' ? 'lessThanOrEqualTo' : 'greaterThanOrEqualTo';
        $comparisonFlag = $flag === 'start' ? 'end_at' : 'start_at';

        $scheduledNovelties = $this->scheduledNovelties($timeClockLog)
        ->filter(
            fn (Novelty $novelty) => ! $novelty->hasTimeClockLog() ||
            $novelty->end_at->between($timeClockLog->checked_in_at->copy()->subMinutes(30), $timeClockLog->checked_out_at->copy()->addMinutes(30))
        );

        $closestScheduledNovelty = $scheduledNovelties
            ->filter(fn (Novelty $novelty) => $novelty->{$comparisonFlag}->{$comparison}($timeClockLog->{$logAction}))
            ->sortBy(fn (Novelty $novelty) => $novelty->{$comparisonFlag}->diffInMinutes($timeClockLog->{$logAction}))
            ->first();

        //     dd($scheduledNovelties
        //     ->filter(fn (Novelty $novelty) => $novelty->{$comparisonFlag}->{$comparison}($timeClockLog->{$logAction})),
        //     $comparisonFlag,
        //     $comparison,
        //     $logAction,
        //     '---',
        //     $scheduledNovelties->pluck($comparisonFlag, 'id'),
        //     $timeClockLog->{$logAction},
        // );

        return $closestScheduledNovelty
            ? Period::make($closestScheduledNovelty->start_at, $closestScheduledNovelty->end_at, Precision::SECOND)
            : null;
    }
}
