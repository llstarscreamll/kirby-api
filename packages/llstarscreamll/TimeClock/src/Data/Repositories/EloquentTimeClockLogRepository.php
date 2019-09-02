<?php

namespace llstarscreamll\TimeClock\Data\Repositories;

use Illuminate\Support\Collection;
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
    protected $fieldSearchable = [
        'employee.user.first_name' => 'like',
        'employee.user.last_name' => 'like',
        'employee.user.email' => 'like',
        'employee.code' => 'like',
        'employee.identification_number' => 'like',
    ];

    /**
     * @var array
     */
    protected $allowedFilters = ['name'];

    /**
     * @var array
     */
    protected $allowedIncludes = [];

    /**
     * @return string
     */
    public function model(): string
    {
        return TimeClockLog::class;
    }

    /**
     * @param  int                 $userId
     * @param  array               $columns
     * @return TimeClockLog|null
     */
    public function lastCheckInWithOutCheckOutFromEmployeeId(int $userId, array $columns = ['*']): ?TimeClockLog
    {
        return $this->model->where(['employee_id' => $userId])
            ->whereNotNull('checked_in_at')
            ->whereNull('checked_out_at')
            ->orderBy('created_at', 'desc')
            ->first($columns);
    }

    /**
     * @param  int                              $employeeId
     * @param  int                              $rows
     * @param  array                            $columns
     * @return \Illuminate\Support\Collection
     */
    public function lastEmployeeLogs(int $employeeId, int $rows = 3, array $columns = ['*']): Collection
    {
        return $this->model->latest()->take($rows)->get($columns);
    }

    /**
     * @param  string  $timeClockLogId
     * @param  string  $userId
     * @return mixed
     */
    public function deleteApproval(string $timeClockLogId, string $userId)
    {
        return $this->find($timeClockLogId)->approvals()->detach($userId);
    }
}
