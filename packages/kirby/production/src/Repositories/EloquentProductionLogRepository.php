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

    /**
     * @inheritdoc
     */
    public function update(int $id, array $data): bool
    {
        return ProductionLog::where('id', $id)->update($data);
    }

    /**
     * @inheritdoc
     */
    public function search(): LengthAwarePaginator
    {
        return QueryBuilder::for(ProductionLog::class)
            ->allowedFilters(['employee_id'])
            ->allowedIncludes(['employee', 'product', 'machine', 'customer'])
            ->defaultSort('-id')
            ->paginate();
    }

    /**
     * @inheritdoc
     */
    public function findById(int $id, $columns = ['*'], $with = []): ?ProductionLog
    {
        return ProductionLog::with($with)->find($id, $columns);
    }
}
