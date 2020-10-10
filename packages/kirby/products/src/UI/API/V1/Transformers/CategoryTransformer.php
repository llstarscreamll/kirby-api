<?php

namespace Kirby\Products\UI\API\V1\Transformers;

use Kirby\Products\Models\Category;
use League\Fractal\TransformerAbstract;

/**
 * Class CategoryTransformer.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CategoryTransformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        'firstTenProducts',
    ];

    /**
     * A Fractal transformer.
     *
     * @param  Category $category
     * @return array
     */
    public function transform(Category $category)
    {
        return [
            'id' => (string) $category->id,
            'name' => (string) $category->name,
            'slug' => (string) $category->slug,
            'image_url' => (string) $category->image_url,
            'position' => (int) $category->position,
            'active' => (bool) $category->active,
            'created_at' => (string) $category->created_at->toISOString(),
            'updated_at' => (string) $category->updated_at->toISOString(),
        ];
    }

    /**
     * @param  Category $category
     * @return mixed
     */
    public function includeFirstTenProducts(Category $category)
    {
        return $this->collection($category->firstTenProducts, new ProductTransformer(), 'Product');
    }
}
