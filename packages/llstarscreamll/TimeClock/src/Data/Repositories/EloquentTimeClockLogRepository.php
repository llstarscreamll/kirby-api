<?php

namespace llstarscreamll\TimeClock\Data\Repositories;

use llstarscreamll\TimeClock\Models\TimeClockLog;
use llstarscreamll\Core\Abstracts\EloquentRepositoryAbstract;
use llstarscreamll\TimeClock\Contracts\TimeClockLogRepositoryInterface;

/**
 * Class EloquentTimeClockLogRepository.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class EloquentTimeClockLogRepository extends EloquentRepositoryAbstract implements TimeClockLogRepositoryInterface
{
    /**
     * @var array
     */
    protected $allowedFilters = ['name'];

    /**
     * @var array
     */
    protected $allowedIncludes = [];

    public function model(): string
    {
        return TimeClockLog::class;
    }

    /**
     * @param  int    $userId
     * @return null
     */
    public function lastCheckInWithOutCheckOutFromEmployeeId(int $userId, array $columns = ['*'])
    {
        return $this->model->where(['employee_id' => $userId])
            ->whereNotNull('checked_in_at')
            ->whereNull('checked_out_at')
            ->orderBy('created_at', 'desc')
            ->first($columns);
    }
}
