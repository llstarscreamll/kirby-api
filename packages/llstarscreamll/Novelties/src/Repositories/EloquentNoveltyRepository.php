<?php

namespace llstarscreamll\Novelties\Repositories;

use Carbon\Carbon;
use llstarscreamll\Novelties\Models\Novelty;
use llstarscreamll\Core\Abstracts\EloquentRepositoryAbstract;
use llstarscreamll\Novelties\Contracts\NoveltyRepositoryInterface;

/**
 * Class EloquentNoveltyRepository.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class EloquentNoveltyRepository extends EloquentRepositoryAbstract implements NoveltyRepositoryInterface
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'noveltyType.code' => '=',
        'noveltyType.name' => 'like',
        'employee.user.first_name' => 'like',
        'employee.user.last_name' => 'like',
        'employee.user.email' => 'like',
        'employee.code' => 'like',
        'employee.identification_number' => 'like',
    ];

    public function model(): string
    {
        return Novelty::class;
    }

    /**
     * @param  $employeeId
     * @param  string        $field
     * @param  Carbon        $start
     * @param  Carbon        $end
     * @return mixed
     */
    public function whereScheduledForEmployee($employeeId, string $field, Carbon $start, Carbon $end)
    {
        $this->model = $this->model
            ->where('employee_id', $employeeId)
            ->whereBetween($field, [$start, $end]);

        return $this;
    }

    /**
     * @param  array  $noveltiesIds
     * @param  int    $approverId
     * @return void
     */
    public function setApprovals(array $noveltiesIds, int $approverId)
    {
        $novelties = $this->model->whereIn('id', $noveltiesIds)->get(['id']);
        $novelties->each->approve($approverId);
    }

    /**
     * @param  array  $noveltiesIds
     * @param  int    $approverId
     * @return void
     */
    public function deleteApprovals(array $noveltiesIds, int $approverId)
    {
        $novelties = $this->model->whereIn('id', $noveltiesIds)->get(['id']);
        $novelties->each->deleteApprove($approverId);
    }

    /**
     * @param  string  $noveltyId
     * @param  string  $userId
     * @return mixed
     */
    public function deleteApproval(string $noveltyId, string $userId)
    {
        return $this->find($noveltyId)->approvals()->detach($userId);
    }
}
