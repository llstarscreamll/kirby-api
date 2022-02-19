<?php

namespace Kirby\Production\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Kirby\Production\Contracts\ProductionReportRepository;

class DBProductionReportRepository implements ProductionReportRepository
{
    /**
     * {@inheritdoc}
     */
    public function getKilogramsAcummulatedByProduct($query = [])
    {
        $start = Carbon::parse(Arr::get($query, 'filter.tag_updated_at.start', now()->subDays(15)));
        $end = Carbon::parse(Arr::get($query, 'filter.tag_updated_at.end', now()->endOfDay()));

        return DB::table('production_logs')
            ->join('products', 'production_logs.product_id', '=', 'products.id')
            ->whereBetween('production_logs.tag_updated_at', [$start, $end])
            ->when(Arr::get($query, 'filter.tags'), fn ($query, $value) => $query->whereIn('tag', $value))
            ->when(Arr::get($query, 'filter.employee_ids'), fn ($query, $value) => $query->whereIn('employee_id', $value))
            ->when(Arr::get($query, 'filter.product_ids'), fn ($query, $value) => $query->whereIn('product_id', $value))
            ->when(Arr::get($query, 'filter.machine_ids'), fn ($query, $value) => $query->whereIn('machine_id', $value))
            ->when(
                Arr::get($query, 'filter.sub_cost_center_ids'),
                fn ($query, $value) => $query
                    ->join('machines', 'production_logs.machine_id', '=', 'machines.id')
                    ->whereIn('machines.sub_cost_center_id', $value)
            )
            ->groupBy('products.id')
            ->orderByRaw('SUM(production_logs.gross_weight - production_logs.tare_weight) desc')
            ->limit(20)
            ->get(['products.id', 'products.short_name', DB::raw('SUM(production_logs.gross_weight - production_logs.tare_weight) as kgs')]);
    }
}
