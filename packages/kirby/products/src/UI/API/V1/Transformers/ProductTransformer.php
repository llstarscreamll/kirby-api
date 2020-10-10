<?php

namespace Kirby\Products\UI\API\V1\Transformers;

use Kirby\Products\Models\Product;
use League\Fractal\TransformerAbstract;

/**
 * Class ProductTransformer.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class ProductTransformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include.
     *
     * @var array
     */
    protected $defaultIncludes = [];

    /**
     * List of resources possible to include.
     *
     * @var array
     */
    protected $availableIncludes = [];

    /**
     * A Fractal transformer.
     *
     * @param  Product $product
     * @return array
     */
    public function transform(Product $product)
    {
        return [
            'id' => (string) $product->id,
            'name' => (string) $product->name,
            'code' => (string) $product->code,
            'slug' => (string) $product->slug,
            'sm_image_url' => (string) $product->sm_image_url,
            'md_image_url' => (string) $product->md_image_url,
            'lg_image_url' => (string) $product->lg_image_url,
            'cost' => (float) $product->cost,
            'price' => (float) $product->price,
            'unity' => (string) $product->unity,
            'pum_unity' => (string) $product->pum_unity,
            'pum_price' => (string) $product->pum_price,
            'quantity' => (float) $product->quantity,
            'active' => (bool) $product->active,
            'created_at' => (string) $product->created_at->toISOString(),
            'updated_at' => (string) $product->updated_at->toISOString(),
        ];
    }
}
