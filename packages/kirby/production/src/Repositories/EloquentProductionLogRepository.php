<?php

namespace Kirby\Production\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Kirby\Production\Contracts\ProductionLogRepository;
use Kirby\Production\Models\ProductionLog;
use Spatie\QueryBuilder\QueryBuilder;

class EloquentProductionLogRepository implements ProductionLogRepository
{
    /**
     * @inheritdoc
     */
    public function create(array $data): ProductionLog
    {
        return ProductionLog::create($data);
    }

    public function search(): LengthAwarePaginator
    {
        return QueryBuilder::for(ProductionLog::class)
            ->allowedFilters(['employee_id'])
            ->allowedIncludes(['employee', 'product', 'machine', 'customer'])
            ->defaultSort('-id')
            ->paginate();
    }
}
