<?php

namespace llstarscreamll\TimeClock\Actions;

use llstarscreamll\Novelties\Contracts\NoveltyTypeRepositoryInterface;
use llstarscreamll\Novelties\Enums\NoveltyTypeOperator;
use llstarscreamll\Novelties\Models\NoveltyType;
use llstarscreamll\TimeClock\Exceptions\InvalidNoveltyTypeException;
use llstarscreamll\TimeClock\Exceptions\TooEarlyToCheckException;
use llstarscreamll\TimeClock\Exceptions\TooLateToCheckException;
use llstarscreamll\WorkShifts\Models\WorkShift;

/**
 * Class ValidateNoveltyTypeBasedOnWorkShiftPunctualityAction.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class ValidateNoveltyTypeBasedOnWorkShiftPunctualityAction
{
    /**
     * @param NoveltyTypeRepositoryInterface $noveltyTypeRepository
     */
    public function __construct(NoveltyTypeRepositoryInterface $noveltyTypeRepository)
    {
        $this->noveltyTypeRepository = $noveltyTypeRepository;
    }

    /**
     * @param  string                     $flag
     * @param  null|WorkShift             $workShift
     * @param  null|array                 $noveltyType
     * @throws TooEarlyToCheckException
     * @throws TooLateToCheckException
     * @return Novelty
     */
    public function run(string $flag, ?WorkShift $workShift, array $noveltyType = null): ?NoveltyType
    {
        if ($noveltyType) {
            $noveltyType = $this->noveltyTypeRepository->find($noveltyType['id']);
        }

        $shiftPunctuality = optional($workShift)->slotPunctuality($flag, now());

        $lateNoveltyOperator = $flag === 'start' ? NoveltyTypeOperator::Subtraction : NoveltyTypeOperator::Addition;
        $eagerNoveltyOperator = $flag === 'start' ? NoveltyTypeOperator::Addition : NoveltyTypeOperator::Subtraction;

        if ($workShift && $shiftPunctuality > 0 && $noveltyType && ! $noveltyType->operator->is($lateNoveltyOperator)) {
            throw new InvalidNoveltyTypeException($shiftPunctuality);
        }

        if ($workShift && $shiftPunctuality < 0 && $noveltyType && ! $noveltyType->operator->is($eagerNoveltyOperator)) {
            throw new InvalidNoveltyTypeException($shiftPunctuality);
        }

        return $noveltyType;
    }
}
