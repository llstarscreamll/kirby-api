<?php

namespace Kirby\Products\UI\API\V1\Controllers;

use Illuminate\Http\Request;
use Kirby\Products\Contracts\CategoryRepository;
use Kirby\Products\UI\API\V1\Transformers\CategoryTransformer;
use Symfony\Component\HttpFoundation\Response;

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

    /**
     * @param Request $request
     * @param string  $id
     */
    public function show(Request $request, string $id)
    {
        if ($request->by_slug) {
            $category = $this->categoryRepository->findWhere(['slug' => $id])->first();
        }

        if (! $request->by_slug) {
            $category = $this->categoryRepository->find($id);
        }

        if (! $category) {
            return response(['errors' => ['code' => Response::HTTP_NOT_FOUND, 'title' => 'Not found']], Response::HTTP_NOT_FOUND);
        }

        return fractal($category, CategoryTransformer::class)
            ->withResourceName('Category');
    }
}
