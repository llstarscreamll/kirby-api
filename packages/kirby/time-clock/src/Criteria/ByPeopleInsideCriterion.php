<?php

namespace Kirby\TimeClock\Criteria;

use Carbon\Carbon;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

class ByPeopleInsideCriterion implements CriteriaInterface
{
    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function apply($model, RepositoryInterface $repository)
    {
        return $model->whereNull('checked_out_at');
    }
}
