<?php

namespace Kirby\Machines\UI\API\V1\Controllers;

use Illuminate\Http\Request;
use Kirby\Core\Filters\QuerySearchFilter;
use Kirby\Machines\Models\Machine;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class MachinesController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(QueryBuilder::for(Machine::query())
            ->allowedFilters(['short_name', AllowedFilter::custom('search', new QuerySearchFilter(['name', 'code']))])
            ->defaultSort('-id')
            ->paginate());
    }
}
