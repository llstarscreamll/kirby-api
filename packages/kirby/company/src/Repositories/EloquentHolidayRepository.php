<?php

namespace Kirby\Company\Repositories;

use Kirby\Company\Contracts\HolidayRepositoryInterface;
use Kirby\Company\Models\Holiday;
use Kirby\Core\Abstracts\EloquentRepositoryAbstract;

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
     * @return mixed
     */
    public function countWhereIn(string $field, array $values)
    {
        return $this->model->whereIn($field, $values)->count();
    }
}
