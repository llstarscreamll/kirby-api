<?php

namespace Kirby\Products\UI\API\V1\Controllers;

use Illuminate\Http\JsonResponse;
use Kirby\Core\Filters\QuerySearchFilter;
use Kirby\Products\Models\Product;
use Kirby\Products\UI\API\V1\Requests\CreateProductRequest;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\Response;

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
                ->allowedFilters([
                    'short_name',
                    AllowedFilter::custom('search', new QuerySearchFilter(['name', 'internal_code'])),
                ])
                ->defaultSort('-id')
                ->paginate()
        );
    }

    public function store(CreateProductRequest $request): JsonResponse
    {
        return response()->json(['data' => Product::create($request->validated())], Response::HTTP_CREATED);
    }
}
