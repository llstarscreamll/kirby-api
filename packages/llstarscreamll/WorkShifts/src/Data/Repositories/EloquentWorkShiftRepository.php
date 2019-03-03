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
    /**
     * @var array
     */
    protected $allowedFilters = ['name'];

    /**
     * @var array
     */
    protected $allowedIncludes = [];

    public function model(): string
    {
        return WorkShift::class;
    }
}
