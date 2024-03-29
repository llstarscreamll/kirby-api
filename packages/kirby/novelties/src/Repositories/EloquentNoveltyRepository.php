<?php

namespace Kirby\Novelties\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Kirby\Core\Abstracts\EloquentRepositoryAbstract;
use Kirby\Novelties\Contracts\NoveltyRepositoryInterface;
use Kirby\Novelties\Models\Novelty;

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
        'start_at' => 'like',
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
     * @return mixed
     */
    public function forEmployeeAndStartDateRange(int $employeeId, Carbon $start, Carbon $end): self
    {
        $this->model = $this->model
            ->where('employee_id', $employeeId)
            ->whereBetween('end_at', [$start->timezone('UTC'), $end->timezone('UTC')]);

        return $this;
    }

    /**
     * @return mixed
     */
    public function whereScheduledForEmployee(int $employeeId, string $field, Carbon $start, Carbon $end): self
    {
        $this->model = $this->model
            ->join('novelty_types', 'novelty_types.id', 'novelties.novelty_type_id')
            ->where('employee_id', $employeeId)
            ->where(
                fn ($q) => $q
            ->where('novelty_types.context_type', '!=', 'normal_work_shift_time')
            ->orWhereNull('novelty_types.context_type')
            )
            ->where(
                fn ($q) => $q->whereBetween('start_at', [$start->timezone('UTC'), $end->timezone('UTC')])
            ->orWhereBetween('end_at', [$start->timezone('UTC'), $end->timezone('UTC')])
            );

        return $this;
    }

    public function setApprovals(array $noveltiesIds, int $approverId): void
    {
        $novelties = $this->model->whereIn('id', $noveltiesIds)->get(['id']);
        $novelties->each->approve($approverId);
    }

    public function deleteApprovals(array $noveltiesIds, int $approverId): void
    {
        $novelties = $this->model->whereIn('id', $noveltiesIds)->get(['id']);
        $novelties->each->deleteApprove($approverId);
    }

    /**
     * @return mixed
     */
    public function deleteApproval(string $noveltyId, string $userId): void
    {
        $this->find($noveltyId)->approvals()->detach($userId);
    }

    public function attachApproversToNovelties(array $approversIds, array $noveltiesIds): bool
    {
        $currentDate = Carbon::now()->toDateTimeString();

        $rows = array_map(fn ($approverId) => array_map(fn ($noveltyId) => [
            'novelty_id' => $noveltyId,
            'user_id' => $approverId,
            'created_at' => $currentDate,
            'updated_at' => $currentDate,
        ], array_unique($noveltiesIds)), array_unique($approversIds));

        return DB::table('novelty_approvals')->insert(Arr::collapse($rows));
    }

    public function findByEmployeeId(int $employeeId): self
    {
        $this->model->where('employee_id', $employeeId);

        return $this;
    }
}
