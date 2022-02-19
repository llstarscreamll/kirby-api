<?php

namespace Kirby\Novelties\Criteria;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

class ByEmployeeIdsCriterion implements CriteriaInterface
{
    /**
     * @var array
     */
    private $employeeIds;

    /**
     * @param int[] $employeeIds
     */
    public function __construct(array $employeeIds)
    {
        $this->employeeIds = $employeeIds;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    public function apply($model, RepositoryInterface $repository)
    {
        return $model->whereIn('novelties.employee_id', $this->employeeIds);
    }
}
