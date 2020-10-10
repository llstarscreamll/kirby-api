<?php

namespace Kirby\Products\Repositories;

use Kirby\Core\Abstracts\EloquentRepositoryAbstract;
use Kirby\Products\Contracts\CategoryRepository;
use Kirby\Products\Models\Category;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Class EloquentCategoryRepository.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class EloquentCategoryRepository extends EloquentRepositoryAbstract implements CategoryRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return Category::class;
    }

    /**
     * @param $limit
     * @param array     $columns
     * @param $method
     */
    public function paginate($limit = null, $columns = ['*'], $method = 'paginate')
    {
        return QueryBuilder::for($this->model())
                ->allowedIncludes('firstTenProducts')
                ->defaultSort('-id')
                ->allowedSorts('position')
                ->allowedFilters([AllowedFilter::exact('active')])
                ->paginate($limit, $columns);
    }
}
