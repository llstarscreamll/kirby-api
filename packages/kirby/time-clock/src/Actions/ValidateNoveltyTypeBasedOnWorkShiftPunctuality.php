<?php

namespace Kirby\TimeClock\Actions;

use Kirby\Novelties\Contracts\NoveltyTypeRepositoryInterface;
use Kirby\Novelties\Enums\NoveltyTypeOperator;
use Kirby\Novelties\Models\NoveltyType;
use Kirby\TimeClock\Exceptions\InvalidNoveltyTypeException;
use Kirby\TimeClock\Exceptions\TooEarlyToCheckException;
use Kirby\TimeClock\Exceptions\TooLateToCheckException;
use Kirby\WorkShifts\Models\WorkShift;

/**
 * Class ValidateNoveltyTypeBasedOnWorkShiftPunctuality.
 *
 * @todo this class needs tests!!
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class ValidateNoveltyTypeBasedOnWorkShiftPunctuality
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
