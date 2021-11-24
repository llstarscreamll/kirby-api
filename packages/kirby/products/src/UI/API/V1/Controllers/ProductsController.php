<?php

namespace Kirby\Products\UI\API\V1\Controllers;

use Kirby\Core\Filters\QuerySearchFilter;
use Kirby\Products\Models\Product;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProductsController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(
            QueryBuilder::for(Product::class)
                ->allowedFilters(['short_name', AllowedFilter::custom('search', new QuerySearchFilter(['name', 'internal_code']))])
                ->defaultSort('-id')
                ->paginate()
            );
    }
}
