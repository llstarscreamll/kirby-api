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
     * @var int
     */
    private $employeeId;

    /**
     * @param int $employeeId
     */
    public function __construct(int $employeeId)
    {
        $this->employeeId = $employeeId;
    }

    /**
     * Apply criteria in query repository.
     *
     * @param  string              $model
     * @param  RepositoryInterface $repository
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository)
    {
        return $model->where('novelties.employee_id', $this->employeeId);
    }
}
