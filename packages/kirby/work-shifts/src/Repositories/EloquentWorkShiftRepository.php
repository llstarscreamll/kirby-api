<?php

namespace Kirby\WorkShifts\Repositories;

use Kirby\Core\Abstracts\EloquentRepositoryAbstract;
use Kirby\WorkShifts\Contracts\WorkShiftRepositoryInterface;
use Kirby\WorkShifts\Models\WorkShift;

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
