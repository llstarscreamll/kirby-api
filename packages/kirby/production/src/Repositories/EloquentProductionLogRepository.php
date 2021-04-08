<?php

namespace Kirby\Production\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Kirby\Core\Filters\QuerySearchFilter;
use Kirby\Production\Models\ProductionLog;
use Kirby\Production\Contracts\ProductionLogRepository;
use Spatie\QueryBuilder\AllowedFilter;
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
        return QueryBuilder::for(ProductionLog::query())
            ->allowedFilters(['employee_id'])
            ->defaultSort('-id')
            ->paginate();
    }
}
