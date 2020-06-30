<?php

namespace Kirby\Novelties\Repositories\Criteria;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class ByNoveltyTypeCriteria.
 */
class ByNoveltyTypeCriteria implements CriteriaInterface
{
    /**
     * @var int[]
     */
    private $noveltyTypeIds;

    /**
     * @param int[] $noveltyTypeIds
     */
    public function __construct(array $noveltyTypeIds)
    {
        $this->noveltyTypeIds = $noveltyTypeIds;
    }

    /**
     * Apply criteria in query repository.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @param  RepositoryInterface                 $repository
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository)
    {
        return $model->whereIn('novelty_type_id', $this->noveltyTypeIds);
    }
}
