<?php

namespace Kirby\TimeClock\Repositories;

use Illuminate\Support\Collection;
use Kirby\Core\Abstracts\EloquentRepositoryAbstract;
use Kirby\TimeClock\Contracts\TimeClockLogRepositoryInterface;
use Kirby\TimeClock\Models\TimeClockLog;

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
        'employee_id' => 'in',
    ];

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

    public function lastCheckInWithOutCheckOutFromEmployeeId(int $userId, array $columns = ['*']): ?TimeClockLog
    {
        $this->applyCriteria();
        $this->applyScope();

        $result = $this->model->where(['employee_id' => $userId])
            ->whereNotNull('checked_in_at')
            ->whereNull('checked_out_at')
            ->orderBy('created_at', 'desc')
            ->first($columns);

        $this->resetModel();
        $this->resetScope();

        return $result;
    }

    public function lastEmployeeLogs(int $employeeId, int $rows = 5, array $columns = ['*']): Collection
    {
        $this->applyCriteria();
        $this->applyScope();

        $result = $this->model
            ->whereEmployeeId($employeeId)
            ->latest()
            ->take($rows)
            ->get($columns);

        $this->resetModel();
        $this->resetScope();

        return $result;
    }

    /**
     * @return mixed
     */
    public function deleteApproval(string $timeClockLogId, string $userId)
    {
        return $this->find($timeClockLogId)->approvals()->detach($userId);
    }

    public function findByEmployeeId(int $employeeId): TimeClockLogRepositoryInterface
    {
        $this->model->where('employee_id', $employeeId);

        return $this;
    }
}
