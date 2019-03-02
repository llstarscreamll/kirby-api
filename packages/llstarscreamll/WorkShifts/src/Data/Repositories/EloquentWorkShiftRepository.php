<?php
namespace llstarscreamll\WorkShifts\Data\Repositories;

use llstarscreamll\Core\Abstracts\EloquentRepositoryAbstract;
use llstarscreamll\WorkShifts\Contracts\WorkShiftRepositoryInterface;
use llstarscreamll\WorkShifts\Models\WorkShift;

/**
 * Class EloquentWorkShiftRepository.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class EloquentWorkShiftRepository extends EloquentRepositoryAbstract implements WorkShiftRepositoryInterface
{
    public function model()
    {
        return WorkShift::class;
    }
}
