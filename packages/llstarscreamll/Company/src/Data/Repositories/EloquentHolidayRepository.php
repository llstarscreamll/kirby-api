<?php

namespace llstarscreamll\Company\Data\Repositories;

use llstarscreamll\Company\Models\Holiday;
use llstarscreamll\Core\Abstracts\EloquentRepositoryAbstract;
use llstarscreamll\Company\Contracts\HolidayRepositoryInterface;

/**
 * Class EloquentHolidayRepository.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class EloquentHolidayRepository extends EloquentRepositoryAbstract implements HolidayRepositoryInterface
{
    /**
     * @var array
     */
    protected $allowedFilters = [];

    /**
     * @var array
     */
    protected $allowedIncludes = [];

    public function model(): string
    {
        return Holiday::class;
    }

    /**
     * @param string $field
     * @param array $values
     * @return mixed
     */
    public function countWhereIn(string $field, array $values)
    {
        return $this->model->whereIn($field, $values)->count();
    }
}
