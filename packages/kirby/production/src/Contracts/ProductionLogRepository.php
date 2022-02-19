<?php

namespace Kirby\Production\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Kirby\Production\Models\ProductionLog;

interface ProductionLogRepository
{
    /**
     * Creates a new record on storage.
     */
    public function create(array $data): ProductionLog;

    /**
     * Updates a record on storage.
     *
     * @return bool Was the record updated?
     */
    public function update(int $id, array $data): bool;

    /**
     * Searches records on storage.
     */
    public function search(): LengthAwarePaginator;

    /**
     * Find a record by the given $id.
     *
     * @param  mixed  $columns
     * @param  mixed  $with
     */
    public function findById(int $id, $columns = ['*'], $with = []): ?ProductionLog;
}
