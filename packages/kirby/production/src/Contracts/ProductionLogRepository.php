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
     * Searches records on storage.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function search(): LengthAwarePaginator;
}
