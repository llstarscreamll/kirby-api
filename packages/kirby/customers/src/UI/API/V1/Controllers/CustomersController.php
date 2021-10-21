<?php

namespace Kirby\Customers\UI\API\V1\Controllers;

use Illuminate\Http\Request;
use Kirby\Core\Filters\QuerySearchFilter;
use Kirby\Customers\Models\Customer;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CustomersController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(QueryBuilder::for(Customer::query())
            ->allowedFilters([AllowedFilter::custom('search', new QuerySearchFilter(['name']))])
            ->defaultSort('-id')
            ->paginate());
    }
}
