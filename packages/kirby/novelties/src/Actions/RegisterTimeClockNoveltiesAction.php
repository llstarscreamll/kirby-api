<?php

namespace Kirby\Novelties\Actions;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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
     * @var NoveltyTypeRepository
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

        $novelties = $this->getApplicableNovelties()
            ->sort(fn(NoveltyType $novelty) => $novelty->isDefaultForSubtraction() ? 9999 : 0)
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

                return array_map(fn(array $period) => [
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
            ->filter(fn($novelty) => ! empty($novelty['total_time_in_minutes']))
            ->map(fn($i) => Arr::except($i, ['code', 'total_time_in_minutes']));

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
            $timeClockLog->checked_out_at && $timeClockLog->checked_in_at->diffInMinutes($timeClockLog->checked_out_at) > 5,
        ];

        return ! in_array(false, $validations);
    }

    /**
     * @param TimeClockLog $timeClockLog
     * @param NoveltyType  $noveltyType
     */
    private function solveNoveltyTypeTime(TimeClockLog $timeClockLog, NoveltyType $noveltyType)
    {
        $this->novelType = $noveltyType;
        $result = new PeriodCollection();
        $launchGapPeriod = $this->solveLaunchGapPeriod($timeClockLog);
        $timeClockPeriod = $this->solveTimeClockPeriod($timeClockLog);
        $workShiftPeriods = $this->solveWorkShiftPeriods($timeClockLog);
        $comparisonBaseWithoutWorkedTime = $this->comparisonBase($timeClockPeriod, $workShiftPeriods);
        // entrega los periodos en los que puede aplicar una novedad, ejemplo:
        // - 2019-04-01 07:00:00 to 2019-04-01 07:59:59
        // no la franja total sino el periodo específico que puede aplicar
        $noveltyTypePeriods = $this->solveNoveltyTypePeriods($timeClockLog, $noveltyType);
        $noveltyWasSelectedByEmployee = in_array($noveltyType->id, [
            $timeClockLog->check_in_novelty_type_id,
            $timeClockLog->check_out_novelty_type_id,
        ]);

        // no hay tiempo de novedades que aplicar
        if (! $noveltyTypePeriods->count()) {
            return [];
        }

        // novedad que aplica como tiempo normal de trabajo
        if ($noveltyType->context_type === 'normal_work_shift_time') {
            $result = $workShiftPeriods
                ->overlap(new PeriodCollection($timeClockPeriod))
                ->overlap($noveltyTypePeriods);
        }

        // la novedad fue elegida por el empleado en la entrada o salida
        if ($noveltyWasSelectedByEmployee) {
            $result = $comparisonBaseWithoutWorkedTime->overlap($noveltyTypePeriods);
        }

        // novedad seleccionada en llegada tarde
        if ($timeClockLog->checkInPunctuality() > 0 && $noveltyType->id === $timeClockLog->check_in_novelty_type_id) {
            $result = new PeriodCollection(...collect([...$noveltyTypePeriods])
                    ->map(fn(Period $n) => [...$n->diff($timeClockPeriod)])
                    ->collapse()
            );
        }

        // novedad seleccionada en salida temprano
        if ($timeClockLog->checkOutPunctuality() < 0 && $noveltyType->id === $timeClockLog->check_out_novelty_type_id) {
            $result = new PeriodCollection(...collect([...$noveltyTypePeriods])
                    ->map(fn(Period $n) => [...$n->diff($timeClockPeriod)])
                    ->collapse()
            );
        }

        // novedad predeterminada para adición de tiempo y el empleado NO la eligió al momento de la entrada
        if (! $noveltyWasSelectedByEmployee && $noveltyType->isDefaultForAddition()) {
            $result = $comparisonBaseWithoutWorkedTime
                ->overlap($noveltyTypePeriods);
        }

        if (! $noveltyWasSelectedByEmployee && $noveltyType->isDefaultForSubtraction()) {
            $result = $comparisonBaseWithoutWorkedTime
                ->overlap($noveltyTypePeriods);
        }

        // a la novedad de horas de trabajo normal se le debe restar el tiempo de almuerzo si así aplica para el turno
        if (! $result->isEmpty() && ! empty($launchGapPeriod) && $noveltyType->context_type === 'normal_work_shift_time') {
            $result = $result->overlap($result[0]->diff($launchGapPeriod));
        }

        // caso especial: tiene turno, pero no hay horas de trabajo "quemadas"
        if ($timeClockLog->hasWorkShift() && $comparisonBaseWithoutWorkedTime->count() === 0 && $timeClockLog->check_in_novelty_type_id === $noveltyType->id) {
            $result = $timeClockPeriod->overlap(...$noveltyTypePeriods);
        }

        // caso especial: tiene turno, pero no hay horas de trabajo "quemadas" y la novedad fue seleccionada en la salida
        if ($timeClockLog->hasWorkShift() && $comparisonBaseWithoutWorkedTime->count() === 0 && $timeClockLog->check_out_novelty_type_id === $noveltyType->id) {
            $result = $workShiftPeriods->overlap($noveltyTypePeriods);
        }

        // hasta este punto algo de tiempo debió haber sido deducido para la
        // novedad, comprobamos que el tiempo no haya sido ya tomado, y si
        // ya está tomado, desvolvemos un array vacío
        $result = $this->subtractTameAlreadyTaken($result, $noveltyType);

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
    private function comparisonBase(Period $timeClockPeriod, PeriodCollection $workShiftPeriods): PeriodCollection
    {
        $base = $timeClockPeriod->diff(...$workShiftPeriods);

        if ($base->count() > 0) {
            return $base;
        }

        $base = collect($workShiftPeriods)
            ->filter(fn(Period $shiftPeriod) => $shiftPeriod->diff($timeClockPeriod)->count())
            ->map(fn(Period $shiftPeriod) => $shiftPeriod->diff($timeClockPeriod))
            ->map(fn(PeriodCollection $shiftPeriod) => [...$shiftPeriod])
            ->collapse();

        return new PeriodCollection(...$base);
    }

    /**
     * @param  PeriodCollection $noveltyTypePeriods
     * @return mixed
     */
    private function subtractTameAlreadyTaken(PeriodCollection $noveltyTypePeriods, $novelty): PeriodCollection
    {
        if ($noveltyTypePeriods->count() === 0) {
            return $noveltyTypePeriods;
        }

        $overlapsWithTakenTimes = collect($this->takenPeriods)
            ->filter()
            ->filter(fn($periods) => array_filter($periods, fn($period) => $period[0]->getTimestamp() - $period[1]->getTimestamp() !== 0))
            ->map(fn($periods) => array_map(fn($period) => [...$period, Precision::SECOND], $periods))
            ->map(fn($periods) => array_map(fn($period) => new Period(...$period), $periods))
            ->collapse()
            ->filter(fn(Period $period) => $period->overlapsWith(...$noveltyTypePeriods));

        if ($overlapsWithTakenTimes->count()) {
            return new PeriodCollection(
                ...$overlapsWithTakenTimes
                    ->map(fn(Period $period) => $period->diff(...$noveltyTypePeriods))
                    ->map(fn(PeriodCollection $periods) => [...$periods])
                    ->collapse()
            );
        }

        return $noveltyTypePeriods;
    }

    /**
     * @param  $item
     * @return mixed
     */
    private function mapper($item)
    {
        if (! is_array($item)) {
            return $item->format('Y-m-d H:i:s');
        }

        return array_map(fn($i) => $this->mapper($i), $item);
    }

    /**
     * @param  TimeClockLog       $timeClockLog
     * @return PeriodCollection
     */
    private function solveWorkShiftPeriods(TimeClockLog $timeClockLog): PeriodCollection
    {
        $workShiftPeriods = collect([]);

        if ($timeClockLog->hasWorkShift()) {
            $workShiftPeriods = $timeClockLog->workShift
                ->mappedTimeSlots($timeClockLog->checked_in_at);
        }

        $workShiftPeriods = $workShiftPeriods
            ->map(fn($slot) => [...$slot, Precision::SECOND])
            ->map(fn($slot) => Period::make(...$slot));

        return new PeriodCollection(...$workShiftPeriods);
    }

    /**
     * @param $timeClockLog
     */
    private function solveTimeClockPeriod($timeClockLog): Period
    {
        $timeClockPeriod = [$timeClockLog->softCheckInAt(), $timeClockLog->softCheckOutAt()];
        array_push($timeClockPeriod, Precision::SECOND);

        return Period::make(...$timeClockPeriod);
    }

    /**
     * @param $noveltyType
     */
    private function solveNoveltyTypePeriods(TimeClockLog $timeClockLog, NoveltyType $noveltyType)
    {
        $basePeriodForNovelty = $this->solveBaseTimeForNovelty($timeClockLog, $noveltyType);
        $scheduledNoveltyPeriod = $this->getTimeFlagOffSetX('end', $timeClockLog);
        $scheduledNoveltyPeriodStart = $this->getTimeFlagOffSetX('start', $timeClockLog);
        $scheduledNoveltyPeriod = array_filter([$scheduledNoveltyPeriod, $scheduledNoveltyPeriodStart]);

        if (count($scheduledNoveltyPeriod)) {
            $basePeriodForNoveltyX = Period::make(...[...$basePeriodForNovelty, Precision::SECOND])
                ->diff(...$scheduledNoveltyPeriod);

            if ($basePeriodForNoveltyX->count()) {
                $basePeriodForNovelty = collect([...$basePeriodForNoveltyX])
                    ->map(fn(Period $period) => [Carbon::make($period->getStart()), Carbon::make($period->getEnd())])
                    ->first();
            }
        }

        $noveltyTypePeriods = $noveltyType->applicablePeriods(...$basePeriodForNovelty)
            ->map(fn($i) => array_filter($i))
            ->filter();

        // caso en el que no hay turno ni novedades
        if (! $timeClockLog->hasWorkShift() &&
            (
                $timeClockLog->check_in_novelty_type_id === $noveltyType->id ||
                // fix check_in_novelty_type_id on second comparison. should be check_out_novelty_type_id
                (empty($timeClockLog->check_in_novelty_type_id) && empty($timeClockLog->check_in_novelty_type_id) && $noveltyType->isDefaultForAddition())
            )
        ) {
            $noveltyTypePeriods = collect([$basePeriodForNovelty]);
        }

        $noveltyTypePeriods = collect($noveltyTypePeriods)
            ->map(fn($slot) => [...$slot, Precision::SECOND])
            ->map(fn($slot) => Period::make(...$slot));

        return (new PeriodCollection(...$noveltyTypePeriods))
            ->overlap(new PeriodCollection(Period::make(...[...$basePeriodForNovelty, Precision::SECOND])));
    }

    /**
     * @param  TimeClockLog  $timeClockLog
     * @return Period|null
     */
    private function solveLaunchGapPeriod(TimeClockLog $timeClockLog): ?Period
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
            ->sortByDesc(fn($slot) => $slot[0]->diffInMinutes($slot[1]))
            ->first();

        [$start, $end] = $workShiftSlot;
        $minutesDiff = ($start->diffInMinutes($end) / 2) - ($mealMinutes / 2);

        $manualGap = [
            $start->copy()->addMinutes($minutesDiff),
            $start->copy()->addMinutes($minutesDiff + $mealMinutes),
            Precision::SECOND,
        ];

        return Period::make(...$manualGap);
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

        if (! $timeClockLog->hasWorkShift()) {
            return [$timeClockLog->checked_in_at, $timeClockLog->checked_out_at];
        }

        // no llegó a tiempo pero no es novedad predeterminada de adición o sustracción
        if ($timeClockLog->checkInPunctuality() !== 0) {
            $start = $timeClockLog->checked_in_at;
        }

        if ($timeClockLog->checkOutPunctuality() !== 0) {
            $end = $timeClockLog->checked_out_at;
        }

        // is la novedad fue seleccionada en la entrada
        if ($noveltyType->id === $timeClockLog->check_in_novelty_type_id && $noveltyType->id !== $timeClockLog->check_out_novelty_type_id) {
            $end = $timeClockLog->expectedCheckOut();
        }

        // si la novedad fue seleccionada en la salida
        if ($noveltyType->id === $timeClockLog->check_out_novelty_type_id && $noveltyType->id !== $timeClockLog->check_in_novelty_type_id) {
            $start = $timeClockLog->expectedCheckIn();
        }

        // si la novedad fue seleccionada en la entrada, es predeterminada para restart y cabe en la salida
        if ($noveltyType->id === $timeClockLog->check_in_novelty_type_id && $noveltyType->isDefaultForSubtraction() && $timeClockLog->checkOutPunctuality() < 0) {
            $end = $timeClockLog->checked_out_at;
        }

        // si la novedad fue seleccionada en la entrada, es predeterminada para sumar y cabe en la salida
        if ($noveltyType->id === $timeClockLog->check_in_novelty_type_id && $noveltyType->isDefaultForAddition() && $timeClockLog->checkOutPunctuality() > 0) {
            $end = $timeClockLog->checked_out_at;
        }

        // si la novedad fue seleccionada en la salida y es predeterminada para restart
        if ($noveltyType->id === $timeClockLog->check_out_novelty_type_id && $noveltyType->isDefaultForSubtraction()) {
            $end = $timeClockLog->expectedCheckOut();
        }

        // si la novedad fue seleccionada en la salida, es predeterminada para sumar
        if ($noveltyType->id === $timeClockLog->check_out_novelty_type_id && $noveltyType->isDefaultForAddition()) {
            $end = $timeClockLog->checked_out_at;
        }

        // too late check in and novelty is the default for subtraction
        if ($timeClockLog->checkInPunctuality() > 0 && $noveltyType->isDefaultForSubtraction()) {
            $start = $timeClockLog->expectedCheckIn();
        }

        if ($timeClockLog->checkOutPunctuality() < 0 && $noveltyType->operator->is(NoveltyTypeOperator::Subtraction())) {
            $end = $timeClockLog->expectedCheckOut();
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
        if (! $this->scheduledNovelties) {
            $beGraceTimeAware = true;

            // scheduled novelties should not exists if work shift is empty
            if (! $timeClockLog->hasWorkShift()) {
                return $this->scheduledNovelties = collect([]);
            }

            $start = $timeClockLog->workShift->minStartTimeSlot($timeClockLog->checked_in_at, $beGraceTimeAware);
            $end = $timeClockLog->workShift->maxEndTimeSlot($timeClockLog->checked_out_at, $beGraceTimeAware);

            $this->scheduledNovelties = $this->noveltyRepository
                ->whereScheduledForEmployee($timeClockLog->employee_id, 'start_at', $start, $end)
                ->get(['novelties.*']);
        }

        return $this->scheduledNovelties;
    }

    /**
     * Get the applicable novelty types to $timeClockLog.
     *
     * @return EloquentCollection
     */
    private function getApplicableNovelties(): EloquentCollection
    {
        return $this->noveltyTypeRepository->all();
    }

    /**
     * @param  TimeClockLog  $timeClockLog
     * @return null|Carbon
     */
    private function getTimeFlagOffSetX(string $flag, TimeClockLog $timeClockLog): ?Period
    {
        $logAction = $flag === 'start' ? 'checked_in_at' : 'checked_out_at';
        $comparison = $flag === 'start' ? 'lessThanOrEqualTo' : 'greaterThanOrEqualTo';
        $comparisonFlag = $flag === 'start' ? 'end_at' : 'start_at';

        $scheduledNovelties = $this->scheduledNovelties($timeClockLog)
            ->filter(function (Novelty $novelty) use ($timeClockLog) {
                return ! $novelty->time_clock_log_id || $novelty->timeClockLog->checked_in_at->between(
                    $timeClockLog->checked_in_at, $timeClockLog->checked_out_at,
                );
            });

        if (! $scheduledNovelties->count()) {
            return null;
        }

        $closestScheduledNovelty = $scheduledNovelties
            ->filter(function (Novelty $novelty) use ($timeClockLog, $comparisonFlag, $comparison, $logAction) {
                return $novelty->{$comparisonFlag}->{$comparison}($timeClockLog->{$logAction});
            })
            ->sortBy(function (Novelty $novelty) use ($timeClockLog, $comparisonFlag, $logAction) {
                return $novelty->{$comparisonFlag}->diffInMinutes($timeClockLog->{$logAction});
            })->first();

        return $closestScheduledNovelty
            ? Period::make($closestScheduledNovelty->start_at, $closestScheduledNovelty->end_at, Precision::SECOND)
            : null;
    }
}
