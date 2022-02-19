<?php

namespace Kirby\Novelties\Repositories\Criteria;

use Carbon\Carbon;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class ByStartDateRangeCriteria.
 */
class ByStartDateRangeCriteria implements CriteriaInterface
{
    /**
     * @var Carbon
     */
    private $start;

    /**
     * @var Carbon
     */
    private $end;

    public function __construct(Carbon $start, Carbon $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    /**
     * Apply criteria in query repository.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository)
    {
        return $model->whereBetween('start_at', [$this->start->toDateTimeString(), $this->end->toDateTimeString()]);
    }
}
