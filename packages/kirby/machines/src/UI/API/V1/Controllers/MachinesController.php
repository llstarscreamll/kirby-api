<?php

namespace Kirby\Machines\UI\API\V1\Controllers;

use Illuminate\Http\JsonResponse;
use Kirby\Machines\Models\Machine;
use Kirby\Machines\UI\API\V1\Requests\SearchMachinesRequest;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class MachinesController
{
    /**
     * Display a listing of the resource.
     */
    public function index(SearchMachinesRequest $request): JsonResponse
    {
        return response()->json(QueryBuilder::for(Machine::query())
            ->join('sub_cost_centers', 'machines.sub_cost_center_id', '=', 'sub_cost_centers.id')
            ->allowedFilters([
                'short_name',
                AllowedFilter::callback('cost_center_ids', fn ($q, $value) => $q->whereIn('sub_cost_centers.cost_center_id', $value)),
            ])
            ->defaultSort('-machines.id')
            ->paginate(null, ['machines.*']));
    }
}
