<?php

namespace Kirby\Production\Repositories;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Kirby\Production\Contracts\ProductionLogRepository;
use Kirby\Production\Models\ProductionLog;
use Kirby\Products\Models\Product;
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
        $now = now()->toDateTimeString();
        $fieldSets = implode(
            ', ',
            array_map(
                fn ($attr) => "$attr = :$attr",
                array_intersect((new ProductionLog)->getFillable(), array_keys($data))
            )
        );

        return DB::statement(<<<MYSQL
            UPDATE production_logs
            SET $fieldSets,
            tag_updated_at = CASE WHEN tag != :tag THEN '$now' ELSE tag_updated_at END
            WHERE id = :id;
        MYSQL, ['id' => $id] + $data);
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
                AllowedFilter::callback('tag_updated_at', function (Builder $query, $value) {
                    $start = Carbon::parse($value['start']);
                    $end = Carbon::parse($value['end']);

                    $query->whereBetween('tag_updated_at', [$start, $end]);
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
