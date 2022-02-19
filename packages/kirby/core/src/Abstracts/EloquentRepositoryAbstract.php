<?php

namespace Kirby\Core\Abstracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Kirby\Core\Filters\QuerySearchFilter;
use Prettus\Repository\Eloquent\BaseRepository;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Class EloquentRepositoryAbstract.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
abstract class EloquentRepositoryAbstract extends BaseRepository
{
    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * @var array
     */
    protected $allowedFilters = [];

    /**
     * @var array
     */
    protected $allowedIncludes = [];

    /**
     * @var \Closure
     */
    protected $scopeQuery;

    /**
     * Get allowed filters.
     *
     * @return array
     */
    public function getAllowedFilters()
    {
        return $this->allowedFilters;
    }

    /**
     * Search resources by url query strings on request.
     *
     * @return $this
     */
    public function search(bool $enableQuerySearchFilter = true)
    {
        $query = $this->model instanceof Builder
            ? $this->model
            : $this->model->query();

        $allowedFilters = $enableQuerySearchFilter
            ? array_merge($this->allowedFilters, [AllowedFilter::custom('search', new QuerySearchFilter($this->allowedFilters))])
            : $this->allowedFilters;

        $this->model = QueryBuilder::for($query)
            ->allowedFilters($allowedFilters)
            ->allowedIncludes($this->allowedIncludes)
            ->defaultSort('-id');

        return $this;
    }

    /**
     * @return mixed
     */
    public function deleteWhereNotIn(string $field, array $values): int
    {
        $this->applyScope();
        $deleted = $this->model->whereNotIn($field, $values)->delete();
        $this->resetModel();

        return $deleted;
    }

    /**
     * Save a new entity in repository.
     *
     * @throws ValidatorException
     *
     * @return mixed
     */
    public function insert(array $rows)
    {
        $result = $this->model->insert($rows);
        $this->resetModel();

        return $result;
    }

    /**
     * Delete data by multiple values in one field.
     *
     * @param       $field
     * @param array $columns
     *
     * @return mixed
     */
    public function deleteWhereIn($field, array $values)
    {
        $this->applyScope();
        $model = $this->model->whereIn($field, $values)->delete();
        $this->resetModel();

        return $this->parserResult($model);
    }

    /**
     * @return mixed
     */
    public function updateWhereIn(string $field, array $values, array $updates)
    {
        $this->applyScope();
        $result = $this->model->whereIn($field, $values)->update($updates);
        $this->resetModel();

        return $result;
    }

    /**
     * Paginate the response.
     *
     * Apply pagination. Use '?limit=' to specify the amount of entities to be
     * returned per page. The client can request all data (skipping pagination)
     * by applying ?limit=0 to the request, if 'repository.pagination.maxLimit'
     * is set to true.
     *
     * @param null   $limit
     * @param array  $columns
     * @param string $method
     *
     * @return mixed
     */
    public function paginate($limit = null, $columns = ['*'], $method = 'paginate')
    {
        // the priority is for the function parameter, if not available then take
        // it from the request if available and if not keep it null.
        $limit = $limit ?: Request::get('limit');
        $maxPaginationLimit = Config::get('repository.pagination.maxLimit');

        // check, if skipping pagination is allowed and requested by the user
        if (Config::get('repository.pagination.skip') && '0' == $limit) {
            return parent::all($columns);
        }

        // check for the maximum entries per pagination
        if (is_int($maxPaginationLimit) && $maxPaginationLimit > 0 && $limit > $maxPaginationLimit) {
            $limit = $maxPaginationLimit;
        }

        return parent::paginate($limit, $columns, $method);
    }
}
