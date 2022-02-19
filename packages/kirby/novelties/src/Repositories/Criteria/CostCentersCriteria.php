<?php

namespace Kirby\Novelties\Repositories\Criteria;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class CostCentersCriteria.
 */
class CostCentersCriteria implements CriteriaInterface
{
    /**
     * @var int[]
     */
    private $costCenterIds;

    /**
     * @param  int[]  $costCenterIds
     */
    public function __construct(array $costCenterIds)
    {
        $this->costCenterIds = $costCenterIds;
    }

    /**
     * Apply criteria in query repository.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository)
    {
        return $model
            ->join('sub_cost_centers', 'sub_cost_centers.id', 'novelties.sub_cost_center_id')
            ->whereIn('sub_cost_centers.cost_center_id', $this->costCenterIds);
    }
}
