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
     * @param mixed $value
     */
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        return $query->where(function ($q) use ($value) {
            foreach ($this->allowedFilters as $key => $filter) {
                0 === $key ? $q->where($filter, 'like', "%{$value}%") : $q->orWhere($filter, 'like', "%{$value}%");
            }
        });
    }
}
