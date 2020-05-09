<?php

namespace Kirby\Novelties\Repositories\Criteria;

use Carbon\Carbon;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class DateRangeCriteria.
 */
class DateRangeCriteria implements CriteriaInterface
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
     * @var string
     */
    private $field;

    /**
     * @param Carbon $start
     * @param Carbon $end
     * @param string $field
     */
    public function __construct(Carbon $start, Carbon $end, string $field = 'created_at')
    {
        $this->start = $start;
        $this->end = $end;
        $this->field = $field;
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
        return $model->whereBetween($this->field, [$this->start->toDateTimeString(), $this->end->toDateTimeString()]);
    }
}
