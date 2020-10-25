<?php

namespace Kirby\Products\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Kirby\Core\Abstracts\EloquentRepositoryAbstract;
use Kirby\Products\Contracts\ProductRepository;
use Kirby\Products\Models\Product;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Class EloquentProductRepository.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class EloquentProductRepository extends EloquentRepositoryAbstract implements ProductRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return Product::class;
    }

    /**
     * @param $limit
     * @param array     $columns
     * @param $method
     */
    public function paginate($limit = null, $columns = ['*'], $method = 'paginate')
    {
        return QueryBuilder::for($this->model())
            ->allowedFilters([
                AllowedFilter::partial('name', 'products.name'),
                AllowedFilter::exact('active', 'products.active'),
                AllowedFilter::callback(
                    'category_slug',
                    fn (Builder $q, string $slug) => $q->where(['categories.slug' => $slug])
                ),
                AllowedFilter::callback(
                    'categories.active',
                    fn (Builder $q, string $active) => $q->where(['categories.active' => filter_var($active, FILTER_VALIDATE_BOOLEAN)])
                ),
            ])
            ->join('category_product', 'category_product.product_id', 'products.id')
            ->join('categories', 'categories.id', 'category_product.category_id')
            ->defaultSort('-products.id')
            ->paginate($limit, $columns);
    }
}
