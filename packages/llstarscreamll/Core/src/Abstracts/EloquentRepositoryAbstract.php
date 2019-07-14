<?php

namespace llstarscreamll\Core\Abstracts;

use Spatie\QueryBuilder\Filter;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Prettus\Repository\Eloquent\BaseRepository;
use llstarscreamll\Core\Filters\QuerySearchFilter;

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
    protected $scopeQuery = null;

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
     * @param  bool    $enableQuerySearchFilter
     * @return $this
     */
    public function search(bool $enableQuerySearchFilter = true)
    {
        $query = $this->model instanceof Builder
            ? $this->model
            : $this->model->query();

        $allowedFilters = $enableQuerySearchFilter
            ? array_merge($this->allowedFilters, [Filter::custom('search', new QuerySearchFilter($this->allowedFilters))])
            : $this->allowedFilters;

        $this->model = QueryBuilder::for($query) 
                ->allowedFilters($allowedFilters)
                ->allowedIncludes($this->allowedIncludes);
        
        return $this;
    }

    /**
     * @param  string  $field
     * @param  array   $values
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
     * @param  array                $rows
     * @throws ValidatorException
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
     * @param  $field
     * @param  array    $values
     * @param  array    $columns
     * @return mixed
     */
    public function deleteWhereIn($field, array $values)
    {
        $this->applyScope();
        $model = $this->model->whereIn($field, $values)->delete();
        $this->resetModel();

        return $this->parserResult($model);
    }
}
