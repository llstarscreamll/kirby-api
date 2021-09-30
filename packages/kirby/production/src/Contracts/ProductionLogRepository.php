<?php

namespace Kirby\Production\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Kirby\Production\Models\ProductionLog;

interface ProductionLogRepository
{
    /**
     * Creates a new record on storage.
     *
     * @param  array                                    $data
     * @return \Kirby\Production\Models\ProductionLog
     */
    public function create(array $data): ProductionLog;

    /**
     * Updates a record on storage.
     *
     * @param  int   $id
     * @param  array $data
     * @return bool  Was the record updated?
     */
    public function update(int $id, array $data): bool;

    /**
     * Searches records on storage.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function search(): LengthAwarePaginator;

    /**
     * Find a record by the given $id.
     */
    public function findById(int $id, $columns = ['*'], $with = []): ?ProductionLog;
}
