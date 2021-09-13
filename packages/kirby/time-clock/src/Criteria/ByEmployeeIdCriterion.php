<?php

namespace Kirby\TimeClock\Criteria;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

class ByEmployeeIdCriterion implements CriteriaInterface
{
    /**
     * @var mixed
     */
    private $employeeId;

    /**
     * @param  int  $employeeId
     */
    public function __construct(int $employeeId)
    {
        $this->employeeId = $employeeId;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  RepositoryInterface  $repository
     */
    public function apply($model, RepositoryInterface $repository)
    {
        return $model->where('employee_id', $this->employeeId);
    }
}
