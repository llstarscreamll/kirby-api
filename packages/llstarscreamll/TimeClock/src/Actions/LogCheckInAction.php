<?php

namespace llstarscreamll\TimeClock\Actions;

use llstarscreamll\Users\Models\User;
use llstarscreamll\WorkShifts\Models\WorkShift;
use llstarscreamll\Novelties\Models\NoveltyType;
use llstarscreamll\TimeClock\Models\TimeClockLog;
use llstarscreamll\Employees\Models\Identification;
use llstarscreamll\Novelties\Enums\NoveltyTypeOperator;
use llstarscreamll\TimeClock\Exceptions\TooLateToCheckException;
use llstarscreamll\TimeClock\Exceptions\TooEarlyToCheckException;
use llstarscreamll\TimeClock\Exceptions\AlreadyCheckedInException;
use llstarscreamll\TimeClock\Exceptions\InvalidNoveltyTypeException;
use llstarscreamll\Novelties\Contracts\NoveltyTypeRepositoryInterface;
use llstarscreamll\TimeClock\Contracts\TimeClockLogRepositoryInterface;
use llstarscreamll\TimeClock\Exceptions\CanNotDeductWorkShiftException;
use llstarscreamll\Employees\Contracts\IdentificationRepositoryInterface;

/**
 * Class LogCheckInAction.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class LogCheckInAction
{
    /**
     * @var IdentificationRepositoryInterface
     */
    private $identificationRepository;

    /**
     * @var TimeClockLogRepositoryInterface
     */
    private $timeClockLogRepository;

    /**
     * @var NoveltyTypeRepositoryInterface
     */
    private $noveltyTypeRepository;

    /**
     * @param NoveltyTypeRepositoryInterface    $noveltyTypeRepository
     * @param IdentificationRepositoryInterface $identificationRepository
     * @param TimeClockLogRepositoryInterface   $timeClockLogRepository
     */
    public function __construct(
        NoveltyTypeRepositoryInterface $noveltyTypeRepository,
        TimeClockLogRepositoryInterface $timeClockLogRepository,
        IdentificationRepositoryInterface $identificationRepository
    ) {
        $this->noveltyTypeRepository = $noveltyTypeRepository;
        $this->timeClockLogRepository = $timeClockLogRepository;
        $this->identificationRepository = $identificationRepository;
    }

    /**
     * @param  User                             $registrar
     * @param  string                           $identificationCode
     * @param  int                              $workShiftId
     * @throws AlreadyCheckedInException
     * @throws CanNotDeductWorkShiftException
     * @throws TooLateToCheckException
     * @return TimeClockLog
     */
    public function run(User $registrar, string $identificationCode, int $workShiftId = null, array $novelty = null): TimeClockLog
    {
        $identification = $this->identificationRepository
            ->with(['employee.workShifts'])
            ->findByField('code', $identificationCode, ['id', 'employee_id'])
            ->first();

        $this->validateUnfinishedCheckIn($identification);
        $workShift = $this->validateDeductibleWorkShift($identification, $workShiftId, $novelty);
        $novelty = $this->validateNoveltyTypeBasedOnWorkShiftPuntuality($workShift, $novelty);

        $timeClockLog = [
            'employee_id' => $identification->employee_id,
            'checked_in_at' => now(),
            'checked_in_by_id' => $registrar->id,
            'work_shift_id' => optional($workShift)->id,
            'novelty_type_id' => optional($novelty)->id,
        ];

        return $this->timeClockLogRepository->create($timeClockLog);
    }

    /**
     * @param Identification $identification
     */
    private function validateUnfinishedCheckIn(Identification $identification): void
    {
        $lastCheckIn = $this->timeClockLogRepository->lastCheckInWithOutCheckOutFromEmployeeId(
            $identification->employee_id,
            ['id', 'checked_in_at']
        );

        if ($lastCheckIn) {
            throw new AlreadyCheckedInException('Ya se registra una entrada.', $lastCheckIn->checked_in_at);
        }
    }

    /**
     * @param  Identification   $identification
     * @param  null|int         $workShiftId
     * @return null|WorkShift
     */
    private function validateDeductibleWorkShift(Identification $identification, ?int $workShiftId): ?WorkShift
    {
        $workShifts = $identification
            ->employee
            ->getWorkShiftsThatMatchesTime(now());

        $employeeWorkShiftsCount = $identification->employee->workShifts->count();

        if ($workShiftId) {
            $workShifts = $identification
                ->employee
                ->workShifts
                ->where('id', $workShiftId);
        }

        $hasWorkShiftsButCantBeDeducted = $employeeWorkShiftsCount > 0 && $workShifts->count() === 0;

        if ($hasWorkShiftsButCantBeDeducted || $workShifts->count() > 1) {
            throw new CanNotDeductWorkShiftException('No fue posible deducir el turno.', $workShifts);
        }

        return $workShifts->first();
    }

    /**
     * @param  null|WorkShift $workShift
     * @param  null|array     $noveltyType
     * @return null|Novelty
     */
    private function validateNoveltyTypeBasedOnWorkShiftPuntuality(?WorkShift $workShift, array $noveltyType = null): ?NoveltyType
    {
        if ($noveltyType) {
            $noveltyType = $this->noveltyTypeRepository->find($noveltyType['id']);
        }

        if ($workShift && $workShift->isOnTimeToStart() < 0 && ! $noveltyType) {
            $noveltyTypes = $this->noveltyTypeRepository->findForTimeAddition();
            throw new TooEarlyToCheckException('Es temprano para registrar la entrada.', $noveltyTypes);
        }

        if ($workShift && $workShift->isOnTimeToStart() > 0 && ! $noveltyType) {
            $noveltyTypes = $this->noveltyTypeRepository->findForTimeSubtraction();
            throw new TooLateToCheckException('Es tarde para registrar la entrada.', $noveltyTypes);
        }

        if ($workShift && $workShift->isOnTimeToStart() > 0 && $noveltyType && ! $noveltyType->operator->is(NoveltyTypeOperator::Subtraction)) {
            $noveltyTypes = $this->noveltyTypeRepository->findForTimeSubtraction();
            throw new InvalidNoveltyTypeException('Tipo de novedad no válido.', $noveltyTypes);
        }

        if ($workShift && $workShift->isOnTimeToStart() < 0 && $noveltyType && ! $noveltyType->operator->is(NoveltyTypeOperator::Addition)) {
            $noveltyTypes = $this->noveltyTypeRepository->findForTimeAddition();
            throw new InvalidNoveltyTypeException('Tipo de novedad no válido.', $noveltyTypes);
        }

        return $noveltyType;
    }
}
