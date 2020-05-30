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
     * @var int
     */
    private $noveltyTypeId;

    /**
     * @param int $noveltyTypeId
     */
    public function __construct(int $noveltyTypeId)
    {
        $this->noveltyTypeId = $noveltyTypeId;
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
        return $model->where('novelty_type_id', $this->noveltyTypeId);
    }
}
