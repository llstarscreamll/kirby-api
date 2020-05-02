<?php

namespace Kirby\Novelties\Actions;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Kirby\Company\Contracts\HolidayRepositoryInterface;
use Kirby\Novelties\Contracts\NoveltyRepositoryInterface;
use Kirby\Novelties\Contracts\NoveltyTypeRepositoryInterface;
use Kirby\Novelties\Enums\DayType;
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
     * @var HolidayRepositoryInterface
     */
    private $holidayRepository;

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
     * @param HolidayRepositoryInterface      $holidayRepository
     * @param NoveltyRepositoryInterface      $noveltyRepository
     * @param NoveltyTypeRepositoryInterface  $noveltyTypeRepository
     * @param TimeClockLogRepositoryInterface $timeClockLogRepository
     */
    public function __construct(
        HolidayRepositoryInterface $holidayRepository,
        NoveltyRepositoryInterface $noveltyRepository,
        NoveltyTypeRepositoryInterface $noveltyTypeRepository,
        TimeClockLogRepositoryInterface $timeClockLogRepository
    ) {
        $this->holidayRepository = $holidayRepository;
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

        $this->attachScheduledNovelties($timeClockLog);

        $novelties = $this->getApplicableNovelties($timeClockLog)
        // ->filter(fn($n) => in_array($n->code, ['HADI']))
            ->sort(fn(NoveltyType $novelty) => $novelty->isDefaultForSubtraction() ? 9999 : 0)
        // ->map->toArray()->dd()
            ->map(function ($noveltyType) use ($timeClockLog, $currentDate) {
                $periods = $this->bar($timeClockLog, $noveltyType);
                $subCostCenterId = $timeClockLog->sub_cost_center_id;

                if ($noveltyType->id === $timeClockLog->check_in_novelty_type_id && $timeClockLog->check_in_sub_cost_center_id) {
                    $subCostCenterId = $timeClockLog->check_in_sub_cost_center_id;
                }

                if ($noveltyType->id === $timeClockLog->check_out_novelty_type_id && $timeClockLog->check_out_sub_cost_center_id) {
                    $subCostCenterId = $timeClockLog->check_out_sub_cost_center_id;
                }

                return array_map(fn(array $period) => [
                    'code' => $noveltyType->code,
                    'time_clock_log_id' => $timeClockLog->id,
                    'employee_id' => $timeClockLog->employee_id,
                    'novelty_type_id' => $noveltyType->id,
                    'sub_cost_center_id' => $subCostCenterId,
                    'scheduled_start_at' => $period[0]->format('Y-m-d H:i:s'),
                    'scheduled_end_at' => $period[1]->format('Y-m-d H:i:s'),
                    'total_time_in_minutes' => (int) (
                        ($period[1]->getTimestamp() - $period[0]->getTimestamp()) / 60
                    ) * ($noveltyType->operator->is(NoveltyTypeOperator::Subtraction()) ? -1 : 1),
                    'created_at' => $currentDate->toDateTimeString(),
                    'updated_at' => $currentDate->toDateTimeString(),
                ], $periods);
            })
            ->filter()
            ->collapse()
            ->filter(fn($novelty) => ! empty($novelty['total_time_in_minutes']))
        // ->dd($this->takenPeriods)
            ->map(fn($i) => Arr::except($i, ['code']));

        $this->noveltyRepository->insert($novelties->all());

        return true;
    }

    /**
     * @param TimeClockLog $timeClockLog
     * @param NoveltyType  $noveltyType
     */
    private function bar(TimeClockLog $timeClockLog, NoveltyType $noveltyType)
    {
        $this->novelType = $noveltyType;
        $result = new PeriodCollection();
        $launchGapPeriod = $this->solveLaunchGapPeriod($timeClockLog);
        $timeClockPeriod = $this->solveTimeClockPeriod($timeClockLog);
        $workShiftPeriods = $this->solveWorkShiftPeriods($timeClockLog);
        $comparisonBaseWithoutWorkedTime = $this->comparisonBase($timeClockPeriod, $workShiftPeriods);
        // entrega los periods en los que puede aplicar una novedad, ejemplo:
        // - 2019-04-01 07:00:00 to 2019-04-01 07:59:59
        // no la franja total sino el periodo específico que puede aplicar
        $noveltyTypePeriods = $this->solveNoveltyTypePeriods($timeClockLog, $noveltyType);
        $noveltyWasSelectedByEmployee = in_array($noveltyType->id, [
            $timeClockLog->check_in_novelty_type_id,
            $timeClockLog->check_out_novelty_type_id,
        ]);

        // dd($workShiftPeriods, $timeClockPeriod, $noveltyTypePeriods);

        if ($noveltyType->code === 'HN') {
            // dd($workShiftPeriods, $timeClockPeriod, $noveltyTypePeriods);
        }

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

        // novedad predeterminada para adición de tiempo y el empleado NO la eligió al momento de la entrada
        if (! $noveltyWasSelectedByEmployee && $noveltyType->isDefaultForAddition()) {
            $result = $comparisonBaseWithoutWorkedTime
                ->overlap($noveltyTypePeriods);
        }

        if (! $noveltyWasSelectedByEmployee && $noveltyType->isDefaultForSubtraction()) {
            $result = $comparisonBaseWithoutWorkedTime
                ->overlap($noveltyTypePeriods);
        }

        // if ($noveltyType->code === 'PP') {dd($result);}

        // novedad que aplica cuando la llegada fue tarde
        // if ($timeClockLog->checkInPunctuality() > 0 && $noveltyType->isDefaultForSubtraction()) {
        //     $result = $workShiftPeriods->overlap($noveltyTypePeriods);
        // }

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

        // if ($noveltyType->code === 'HEDI') {
        //     dd($result, $this->takenPeriods, $noveltyType->code);
        // }

        // hasta este punto algo de tiempo debió haber sido deducido para la
        // novedad, comprobamos que el tiempo no haya sido ya tomado, y si
        // ya está tomado, desvolvemos un array vacío
        $result = $this->subtractTameAlreadyTaken($result, $noveltyType);

        // if ($noveltyType->code === 'RECNO') {
        //     dd($result, $this->takenPeriods);
        // }

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

        /*if ($novelty->code === 'RECNO') {
        dd(
        $overlapsWithTakenTimes,
        $noveltyTypePeriods,
        new PeriodCollection(
        ...$overlapsWithTakenTimes
        ->map(fn(Period $period) => $period->diff(...$noveltyTypePeriods))
        ->map(fn(PeriodCollection $periods) => [...$periods])
        ->collapse()
        ),
        'FOOO'
        );
        }*/

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

        // dd($basePeriodForNovelty, $noveltyType->applicablePeriods(...$basePeriodForNovelty), 'foo');

        if ($this->novelType->code === 'HN') {
            //dd($scheduledNoveltyPeriod, $basePeriodForNovelty, $noveltyType->applicablePeriods(...$basePeriodForNovelty));
        }

        // if ($noveltyType->id === 1) {
        //     dd($basePeriodForNovelty);
        // }

        try {
            if (count($scheduledNoveltyPeriod)) {
                $basePeriodForNoveltyX = Period::make(...[...$basePeriodForNovelty, Precision::SECOND])
                    ->diff(...$scheduledNoveltyPeriod);

                // if ($noveltyType->code === 'PP') {
                //     dd($basePeriodForNovelty, $scheduledNoveltyPeriod);
                // }

                if ($basePeriodForNoveltyX->count()) {
                    $basePeriodForNovelty = collect([...$basePeriodForNoveltyX])
                        ->map(fn(Period $period) => [Carbon::make($period->getStart()), Carbon::make($period->getEnd())])
                        ->first();
                }
            }

            // if ($noveltyType->id === 1) {
            //     dd($basePeriodForNovelty, $noveltyType->applicablePeriods(...$basePeriodForNovelty));
            // }

            $noveltyTypePeriods = $noveltyType->applicablePeriods(...$basePeriodForNovelty)
            // ->dd($basePeriodForNovelty, $noveltyType->code)
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
        } catch (\Throwable $th) {
            dd('ERRORRR', $th, $basePeriodForNovelty, $noveltyType->id);
        }

        // if ($noveltyType->code === 'PP') {
        //     dd(
        //         collect($this->takenPeriods)
        //     ->filter()
        //     ->filter(fn($periods) => array_filter($periods, fn($period) => $period[0]->getTimestamp() - $period[1]->getTimestamp() !== 0))
        //     ->map(fn($periods) => array_map(fn($period) => [...$period, Precision::SECOND], $periods))
        //     ->map(fn($periods) => array_map(fn($period) => new Period(...$period), $periods))
        //     ->collapse()
        //     ->filter(fn(Period $period) => $period->overlapsWith(...$noveltyTypePeriods))
        //     );
        // }

        return (new PeriodCollection(...$noveltyTypePeriods))->overlap(new PeriodCollection(Period::make(...[...$basePeriodForNovelty, Precision::SECOND])));
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

        // if ($noveltyType->id === 1) {dd('', (string) $start, (string) $end, $noveltyType->toArray());}
        // dd(array_map('strval', [$start, $end]));

        return [$start->setTimezone('UTC'), $end->setTimezone('UTC')];
    }

    /**
     * @param TimeClockLog $timeClockLog
     * @param NoveltyType  $noveltyType
     */
    public function foo(TimeClockLog $timeClockLog, NoveltyType $noveltyType)
    {
        $shiftStart = $startDate = $timeClockLog->checked_in_at;
        $shiftEnd = $endDate = $timeClockLog->checked_out_at;

        // if has work shift, then deduce start and end dates based on work shift time slots
        if ($timeClockLog->hasWorkShift()) {
            $workShift = $timeClockLog->workShift;
            [$_, $shiftStart] = array_values($workShift->matchingTimeSlot('start', $timeClockLog->checked_in_at));
            [$shiftEnd, $_] = array_values($workShift->matchingTimeSlot('end', $timeClockLog->checked_out_at));

            $workShift->startPunctuality($timeClockLog->checked_in_at) === 0
                ? $startDate = $shiftStart : null;
            $workShift->endPunctuality($timeClockLog->checked_out_at) === 0
                ? $endDate = $shiftEnd : null;
        }

        $timeClockPeriod = Period::make($startDate, $endDate, Precision::SECOND);
        $noveltyPeriod = $noveltyType->foo(
            $noveltyType->context_type === 'normal_work_shift_time' ? $shiftStart : $startDate,
            $noveltyType->context_type === 'normal_work_shift_time' ? $shiftEnd : $endDate
        ); // novelty period

        if (! count($noveltyPeriod)) {
            return [];
        }

        [$noveltyStart, $noveltyEnd] = $noveltyType->foo(
            $noveltyType->context_type === 'normal_work_shift_time' ? $shiftStart : $startDate,
            $noveltyType->context_type === 'normal_work_shift_time' ? $shiftEnd : $endDate
        );

        $noveltyPeriod = Period::make($noveltyStart, $noveltyEnd, Precision::SECOND);
        $applicablePeriod = $timeClockPeriod->overlap($noveltyPeriod);

        return $noveltyType->context_type === 'normal_work_shift_time'
            ? $applicablePeriod->reduce(fn($acc, Period $period) => $acc[] = [$period], [])
            : [];
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
                ->whereScheduledForEmployee($timeClockLog->employee_id, 'scheduled_start_at', $start, $end)
                ->get();
        }

        return $this->scheduledNovelties;
    }

    /**
     * Get the applicable novelty types to $timeClockLog.
     *
     * @param  TimeClockLog         $timeClockLog
     * @return EloquentCollection
     */
    private function getApplicableNovelties(TimeClockLog $timeClockLog): EloquentCollection
    {
        $noveltyTypeIds = array_filter([
            $timeClockLog->check_in_novelty_type_id,
            $timeClockLog->check_out_novelty_type_id,
        ]);

        return $this->noveltyTypeRepository->all();

        $dayTypes = [DayType::Workday];
        $timeClockLog->hasHolidaysChecks() ? array_push($dayTypes, DayType::Holiday) : null;
        $this->noveltyTypeRepository->whereDayType($dayTypes);

        $scheduledNovelty = $this->noveltyRepository
            ->whereScheduledForEmployee(
                $timeClockLog->employee->id,
                'scheduled_end_at',
                $timeClockLog->checked_in_at->copy()->subMinutes(30),
                $timeClockLog->checked_in_at->copy()->addMinutes(30)
            )
            ->orderBy('id', 'DESC')
            ->first();

        if ($timeClockLog->checkInPunctuality(optional($scheduledNovelty)->scheduled_end_at) === 1 || $timeClockLog->checkOutPunctuality() === -1) {
            $this->noveltyTypeRepository->orWhereDefaultForSubtraction();
        }

        if ($timeClockLog->hasHolidaysChecks() || ! $timeClockLog->hasWorkShift() || ($timeClockLog->hasWorkShift() && $timeClockLog->workShift->hasDeadTimes())) {
            $this->noveltyTypeRepository->orWhereDefaultForAddition();
        }

        $noveltyTypes = $noveltyTypeIds
            ? $this->noveltyTypeRepository->findOrWhereIn('id', $noveltyTypeIds)
            : $this->noveltyTypeRepository->get();

        $noveltyTypes = $noveltyTypes->filter(function (NoveltyType $noveltyType) use ($timeClockLog) {
            if (empty($noveltyType->apply_on_time_slots) || $noveltyType->isDefaultForAddition() || $noveltyType->isDefaultForSubtraction()) {
                return true;
            }

            $start = $noveltyType->minStartTimeSlot($timeClockLog->checked_in_at);
            $end = $noveltyType->maxEndTimeSlot($timeClockLog->checked_in_at);

            $relativeTo = $timeClockLog->checked_in_at;
            $beGraceTimeAware = false;
            $relativeToEnd = false;

            return $timeClockLog->hasWorkShift() && (
                $timeClockLog->workShift->isMinStartTimeSlotInRage($start, $end, $relativeTo) ||
                $timeClockLog->workShift->isMaxEndTimeSlotInRange($start, $end, $relativeTo, $beGraceTimeAware, $relativeToEnd)
            );
        });

        return $noveltyTypes;
    }

    /**
     * @param  TimeClockLog $timeClockLog
     * @param  NoveltyType  $noveltyType
     * @return array
     */
    private function solveTimeForNoveltyType(TimeClockLog $timeClockLog, NoveltyType $noveltyType): array
    {
        $timeInMinutes = 0;
        $noveltyPeriod = new PeriodCollection();
        $deadTimeInMinutes = 0;
        $subCostCenterId = $timeClockLog->sub_cost_center_id;
        $workShift = optional($timeClockLog->workShift);
        $checkInNoveltyTypeId = $timeClockLog->check_in_novelty_type_id;
        $checkOutNoveltyTypeId = $timeClockLog->check_out_novelty_type_id;
        [$startNoveltyMinutes, $clockedMinutes, $endNoveltyMinutes, $mealMinutes, $times] = $this->calculateTimeClockLogTimesInMinutes($timeClockLog);

        $checkedInAt = $timeClockLog->checked_in_at;
        $checkedOutAt = $timeClockLog->checked_out_at;
        $checkOutPunctuality = $timeClockLog->checkOutPunctuality();
        $checkInPunctuality = $timeClockLog->checkInPunctuality();
        $tooLateCheckIn = $checkInPunctuality === 1;
        $tooEarlyCheckOut = $checkOutPunctuality === -1;

        $noveltyType->canApplyOnDayType(DayType::Holiday())
            ? [$a, $b] = $times[DayType::Holiday.'Times']
            : [$a, $b] = $times[DayType::Workday.'Times'];
        $dayTypePeriod = Period::make($a, $b, Precision::SECOND);

        // solve dead time on work shift
        if ($timeClockLog->hasWorkShift()) {
            $deadTimeInMinutes = $workShift->deadTimeInMinutesFromTimeRange($checkedInAt, $checkedOutAt);
        }

        if ($timeClockLog->hasWorkShift() && $noveltyType->context_type === 'normal_work_shift_time' && $clockedMinutes[$noveltyType->apply_on_days_of_type->value]) {
            if ($workShift && $timeClockLog->hasClockedTimeOnWorkShift()) {
                // on time or early
                $startTime = in_array($checkInPunctuality, [-1, 0])
                    ? $workShift->getClosestSlotFlagTime('start', $checkedInAt)
                    : $checkedInAt;

                // on time or late
                $endTime = in_array($checkOutPunctuality, [0, 1])
                    ? $workShift->getClosestSlotFlagTime('end', $checkedOutAt)
                    : $checkedOutAt;

                $timeInMinutes = $noveltyType->applicableTimeInMinutesFromTimeRange($startTime, $endTime);
                $noveltyPeriod = Period::make($startTime, $endTime, Precision::SECOND);

                if ($noveltyType->foo($startTime, $endTime)) {
                    [$noveltyStart, $noveltyEnd] = $noveltyType->foo($startTime, $endTime);
                    $noveltyPeriod = Period::make($noveltyStart, $noveltyEnd, Precision::SECOND);
                }

                // $period->diff($b);
                $timeInMinutes -= $deadTimeInMinutes;
            }

            $shouldDiscountMealTime = $timeClockLog->workShift->canMealTimeApply($timeInMinutes);

            $noveltyType->canApplyOnDayType(DayType::Holiday())
                ? [$a, $b] = $times[DayType::Holiday.'Times']
                : [$a, $b] = $times[DayType::Workday.'Times'];
            $dayTypePeriod = Period::make($a, $b, Precision::SECOND);
            $noveltyPeriod = $noveltyPeriod->diffSingle($dayTypePeriod);

            $timeInMinutes -= $noveltyType->canApplyOnDayType(DayType::Holiday())
                ? $clockedMinutes[DayType::Holiday]
                : $clockedMinutes[DayType::Workday];

            if ($shouldDiscountMealTime) {
                // $period->diff();
                $timeInMinutes -= $mealMinutes;
            }
        }

        // $noveltyPeriod = $noveltyType->apply_on_days_of_type
        //     ? $noveltyType->canApplyOnDayType(DayType::Holiday()) ? Arr::get($times, 'holidayTimes') : Arr::get($times, 'workdayTimes')
        //     : $this->getWiderTimes($times);

        $clockedMinutes = $noveltyType->apply_on_days_of_type
            ? $clockedMinutes[$noveltyType->apply_on_days_of_type->value]
            : array_sum($clockedMinutes);

        if ($checkInNoveltyTypeId === $noveltyType->id && $timeClockLog->hasWorkShift()) {
            $subCostCenterId = $timeClockLog->check_in_sub_cost_center_id ?? $subCostCenterId;
            $timeInMinutes += $startNoveltyMinutes;

            if (count($times['startNoveltyTimes'])) {
                [$a, $b] = $times['startNoveltyTimes'];
                $noveltyPeriod = $noveltyPeriod->boundaries(Period::make($a, $b, Precision::SECOND));
            }
        }

        if ($checkOutNoveltyTypeId === $noveltyType->id && $timeClockLog->hasWorkShift()) {
            $subCostCenterId = $timeClockLog->check_out_sub_cost_center_id ?? $subCostCenterId;
            $timeInMinutes += $endNoveltyMinutes;

            if (count($times['endNoveltyTimes'])) {
                [$a, $b] = $times['endNoveltyTimes'];
                $noveltyPeriod = $noveltyPeriod->boundaries(Period::make($a, $b, Precision::SECOND));
            }
        }

        if (! $checkInNoveltyTypeId && $tooLateCheckIn && $noveltyType->isDefaultForSubtraction()) {
            $timeInMinutes += $startNoveltyMinutes;

            if (count($times['startNoveltyTimes'])) {
                [$a, $b] = $times['startNoveltyTimes'];
                $noveltyPeriod = $noveltyPeriod->boundaries(Period::make($a, $b, Precision::SECOND));
            }
        }

        if (! $checkOutNoveltyTypeId && $tooEarlyCheckOut && $noveltyType->isDefaultForSubtraction()) {
            $timeInMinutes += $endNoveltyMinutes;

            if (count($times['endNoveltyTimes'])) {
                [$a, $b] = $times['endNoveltyTimes'];
                $noveltyPeriod = $noveltyPeriod->boundaries(Period::make($a, $b, Precision::SECOND));
            }
        }

        if (! $timeClockLog->hasWorkShift() && ($noveltyType->id === $checkInNoveltyTypeId || $noveltyType->isDefaultForAddition())) {
            $timeInMinutes = $clockedMinutes;
            $noveltyPeriod = $dayTypePeriod;
        }

        if ($noveltyType->isDefaultForAddition()) {
            $timeInMinutes += $deadTimeInMinutes;
        }

        return [$timeInMinutes, $subCostCenterId, $noveltyPeriod->reduce(fn($acc, Period $period) => $acc[] = [$period], [])];
    }

    /**
     * @param array $times
     */
    private function getWiderTimes(array $times): array
    {
        $holidayTimes = $times['holidayTimes'];
        $workdayTimes = $times['workdayTimes'];

        if (empty($holidayTimes) || empty($workdayTimes)) {
            return $holidayTimes ?? $workdayTimes;
        }

        [$holidayStartTime, $holidayEndTime] = $holidayTimes;
        [$workdayStartTime, $workdayEndTime] = $workdayTimes;

        return [
            $holidayStartTime->lessThan($workdayStartTime) ? $holidayStartTime : $workdayStartTime,
            $holidayEndTime->greaterThan($workdayEndTime) ? $holidayEndTime : $workdayEndTime,
        ];
    }

    /**
     * Calculate time clock times: start novelty type, work time and end novelty
     * type.
     *
     * @param  TimeClockLog $timeClockLog
     * @return array
     */
    private function calculateTimeClockLogTimesInMinutes(TimeClockLog $timeClockLog): array
    {
        $times = [];
        $workMinutes = [
            DayType::Holiday => 0,
            DayType::Workday => 0,
        ];
        $startNoveltyMinutes = 0;
        $endNoveltyMinutes = 0;
        $workShift = optional($timeClockLog->workShift);
        $mealMinutes = $workShift->meal_time_in_minutes ?? 0;
        $closestStartSlot = $workShift->getClosestSlotFlagTime('start', $timeClockLog->checked_in_at, $this->getTimeFlagOffSet('start', $timeClockLog));
        $closestEndSlot = $workShift->getClosestSlotFlagTime('end', $timeClockLog->checked_out_at, $this->getTimeFlagOffSet('end', $timeClockLog));

        // calculate check in novelty time
        if ($timeClockLog->hasWorkShift()) {
            // check out is before closest work shift start slot?
            $estimatedStartTime = $timeClockLog->checked_out_at->lessThan($closestStartSlot)
                ? $timeClockLog->checked_out_at
                : $closestStartSlot;

            $startNoveltyMinutes = $timeClockLog->checkInPunctuality() !== 0
                ? $estimatedStartTime->diffInMinutes($timeClockLog->checked_in_at)
                : 0;

            $times['startNoveltyTimes'] = ['start' => $estimatedStartTime, 'end' => $timeClockLog];

            if (! $timeClockLog->checkInNovelty || $timeClockLog->checkInNovelty->operator->is(NoveltyTypeOperator::Subtraction)) {
                $startNoveltyMinutes *= -1;
            }
        }

        // calculate check out novelty time
        if ($timeClockLog->hasWorkShift()) {
            $estimatedEndTime = $timeClockLog->checked_out_at->lessThan($closestStartSlot)
                ? $closestStartSlot : $timeClockLog->checked_out_at;

            $endNoveltyMinutes = $closestEndSlot->diffInMinutes($estimatedEndTime);
            $times['endNoveltyMinutes'] = ['start' => $closestEndSlot, 'end' => $estimatedEndTime];

            if (! $timeClockLog->checkOutNovelty || $timeClockLog->checkOutNovelty->operator->is(NoveltyTypeOperator::Subtraction)) {
                $endNoveltyMinutes *= -1;
            }
        }

        [$holidayTimeInMinutes, $holidayTimes] = $timeClockLog->getClockedTimeMinutesByDayType(DayType::Holiday());
        $workMinutes[DayType::Holiday] += $holidayTimeInMinutes;
        $times['holidayTimes'] = $holidayTimes;

        [$workdayTimeInMinutes, $workdayTimes] = $timeClockLog->getClockedTimeMinutesByDayType(DayType::Workday());
        $workMinutes[DayType::Workday] += $workdayTimeInMinutes;
        $times['workdayTimes'] = $workdayTimes;

        return [
            $startNoveltyMinutes,
            $workMinutes,
            $endNoveltyMinutes,
            $mealMinutes,
            $times,
        ];
    }

    /**
     * @param  TimeClockLog  $timeClockLog
     * @return null|Carbon
     */
    private function getTimeFlagOffSet(string $flag, TimeClockLog $timeClockLog): ?Carbon
    {
        $logAction = $flag === 'start' ? 'checked_in_at' : 'checked_out_at';
        $comparison = $flag === 'start' ? 'lessThanOrEqualTo' : 'greaterThanOrEqualTo';
        $comparisonFlag = $flag === 'start' ? 'scheduled_end_at' : 'scheduled_start_at';

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

        return optional($closestScheduledNovelty)->{$comparisonFlag}; // ?? $timeClockLog->{$logAction};
    }

    /**
     * @param  TimeClockLog  $timeClockLog
     * @return null|Carbon
     */
    private function getTimeFlagOffSetX(string $flag, TimeClockLog $timeClockLog): ?Period
    {
        $logAction = $flag === 'start' ? 'checked_in_at' : 'checked_out_at';
        $comparison = $flag === 'start' ? 'lessThanOrEqualTo' : 'greaterThanOrEqualTo';
        $comparisonFlag = $flag === 'start' ? 'scheduled_end_at' : 'scheduled_start_at';

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
            ? Period::make($closestScheduledNovelty->scheduled_start_at, $closestScheduledNovelty->scheduled_end_at, Precision::SECOND)
            : null;
    }
}
