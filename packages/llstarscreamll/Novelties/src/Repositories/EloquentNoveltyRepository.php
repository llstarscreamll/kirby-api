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
            ->whereNull('time_clock_log_id')
            ->where('employee_id', $employeeId)
            ->whereBetween($field, [$start, $end]);

        return $this;
    }
}
