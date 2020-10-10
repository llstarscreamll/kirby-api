<?php

namespace Kirby\Products\UI\API\V1\Controllers;

use Illuminate\Http\Request;
use Kirby\Products\Contracts\ProductRepository;
use Kirby\Products\UI\API\V1\Transformers\ProductTransformer;

/**
 * Class ProductsController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class ProductsController
{
    /**
     * @var \Kirby\Products\Contracts\ProductRepository
     */
    private $productRepository;

    /**
     * @param ProductRepository $productRepository
     */
    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * @param  Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $paginatedCategories = $this->productRepository
            ->paginate(min($request->get('limit', 10), 100), ['products.*']);

        return fractal($paginatedCategories, ProductTransformer::class)
            ->withResourceName('Product');
    }
}
