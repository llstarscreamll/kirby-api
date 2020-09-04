<?php

namespace Kirby\Products\UI\API\V1\Controllers;

use Illuminate\Http\Request;
use Kirby\Products\Contracts\CategoryRepository;
use Kirby\Products\UI\API\V1\Transformers\CategoryTransformer;

/**
 * Class CategoriesController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CategoriesController
{
    /**
     * @var \Kirby\Products\Contracts\CategoryRepository
     */
    private $categoryRepository;

    /**
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param  Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $paginatedCategories = $this->categoryRepository
            ->paginate(min($request->get('limit', 10), 100));

        return fractal($paginatedCategories, CategoryTransformer::class)
            ->withResourceName('Category');
    }
}
