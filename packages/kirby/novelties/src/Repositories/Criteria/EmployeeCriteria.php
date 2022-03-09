<?php

namespace Kirby\Novelties\Repositories\Criteria;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class EmployeeCriteria.
 */
class EmployeeCriteria implements CriteriaInterface
{
    /**
     * @var int[]
     */
    private $employeeIds;

    /**
     * @param  int[]  $employeeIds
     */
    public function __construct(array $employeeIds)
    {
        $this->employeeIds = $employeeIds;
    }

    /**
     * Apply criteria in query repository.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository)
    {
        return $model->whereIn('novelties.employee_id', $this->employeeIds);
    }
}
