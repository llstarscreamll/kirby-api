<?php

namespace Kirby\Novelties\Repositories\Criteria;

use Carbon\Carbon;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class HasTimeClockLogCheckOutBetweenCriteria.
 */
class HasTimeClockLogCheckOutBetweenCriteria implements CriteriaInterface
{
    /**
     * @var Carbon
     */
    private $start;

    /**
     * @var Carbon
     */
    private $end;

    /**
     * @param Carbon $start
     * @param Carbon $end
     */
    public function __construct(Carbon $start, Carbon $end)
    {
        $this->start = $start;
        $this->end = $end;
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
        return $model
            ->join('time_clock_logs', 'time_clock_logs.id', 'novelties.time_clock_log_id')
            ->whereBetween('time_clock_logs.checked_out_at', [$this->start->toDateTimeString(), $this->end->toDateTimeString()]);
    }
}
