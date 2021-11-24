<?php

namespace Kirby\Production\Repositories;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Kirby\Production\Contracts\ProductionLogRepository;
use Kirby\Production\Models\ProductionLog;
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
            ->allowedFilters([
                AllowedFilter::exact('employee_id'),
                AllowedFilter::exact('product_id'),
                AllowedFilter::exact('machine_id'),
                AllowedFilter::callback('creation_date', function (Builder $query, $value) {
                    $start = Carbon::parse($value['start']);
                    $end = Carbon::parse($value['end']);

                    $query->whereBetween('created_at', [$start, $end]);
                }),
                AllowedFilter::callback('net_weight', function (Builder $query, $value) {
                    // the (? + 0.0) is a hack to make this query compatible with sqlite, see:
                    //https://github.com/laravel/framework/issues/31201#issuecomment-615682788
                    $query->whereRaw('gross_weight - tare_weight = (? + 0.0)', [$value]);
                }),
            ])
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
