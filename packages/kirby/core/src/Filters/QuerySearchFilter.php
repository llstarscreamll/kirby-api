<?php

namespace Kirby\Core\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

/**
 * Class QuerySearchFilter.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class QuerySearchFilter implements Filter
{
    /**
     * @var array
     */
    private $allowedFilters;

    /**
     * @param  array  $allowedFilters
     */
    public function __construct(array $allowedFilters = [])
    {
        $this->allowedFilters = collect($allowedFilters)
            ->unique()
            ->filter(function ($filter) {
                return is_string($filter);
            })
            ->values();
    }

    /**
     * @param  Builder  $query
     * @param  mixed  $value
     * @param  string  $property
     * @return Builder
     */
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        return $query->where(function ($q) use ($value) {
            foreach ($this->allowedFilters as $key => $filter) {
                $key === 0 ? $q->where($filter, 'like', "%$value%") : $q->orWhere($filter, 'like', "%$value%");
            }
        });
    }
}
