<?php

namespace llstarscreamll\WorkShifts\Data\Repositories;

use llstarscreamll\WorkShifts\Models\WorkShift;
use llstarscreamll\Core\Abstracts\EloquentRepositoryAbstract;
use llstarscreamll\WorkShifts\Contracts\WorkShiftRepositoryInterface;

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

    /**
     * @param  string  $field
     * @param  array   $values
     * @return mixed
     */
    public function deleteWhereNotIn(string $field, array $values): int
    {
        $this->applyScope();
        $deleted = $this->model->whereNotIn($field, $values)->delete();
        $this->resetModel();

        return $deleted;
    }
}
