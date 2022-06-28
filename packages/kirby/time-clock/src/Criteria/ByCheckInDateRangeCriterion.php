<?php

namespace Kirby\TimeClock\Criteria;

use Carbon\Carbon;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

class ByCheckInDateRangeCriterion implements CriteriaInterface
{
    /** @var \Carbon\Carbon */
    private $start;

    /** @var \Carbon\Carbon */
    private $end;

    public function __construct(Carbon $start, Carbon $end)
    {
        $this->end = $end;
        $this->start = $start;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function apply($model, RepositoryInterface $repository)
    {
        return $model->whereBetween('checked_in_at', [$this->start, $this->end]);
    }
}
