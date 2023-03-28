<?php

namespace Kirby\Novelties\Repositories;

use Illuminate\Support\Collection;
use Kirby\Core\Abstracts\EloquentRepositoryAbstract;
use Kirby\Novelties\Contracts\NoveltyTypeRepositoryInterface;
use Kirby\Novelties\Enums\NoveltyTypeOperator;
use Kirby\Novelties\Models\NoveltyType;

/**
 * Class EloquentNoveltyTypeRepository.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class EloquentNoveltyTypeRepository extends EloquentRepositoryAbstract implements NoveltyTypeRepositoryInterface
{
    /**
     * @var array
     */
    protected $allowedFilters = ['name'];

    /**
     * Fields that are searchable by \Prettus\Repository\Criteria\RequestCriteria.
     *
     * @var array
     */
    protected $fieldSearchable = [
        'code' => 'like',
        'name' => 'like',
    ];

    /**
     * @var array
     */
    protected $allowedIncludes = [];

    public function model(): string
    {
        return NoveltyType::class;
    }

    /**
     * @param  array  $columns
     * @return mixed
     */
    public function findByCode(string $code, $columns = ['*'])
    {
        $this->applyScope();

        $results = $this->model->whereCode($code)->first($columns);

        $this->resetModel();

        return $this->parserResult($results);
    }

    /**
     * @param  array  $columns
     */
    public function findForTimeSubtraction($columns = ['*']): Collection
    {
        return $this->findWhere(['operator' => NoveltyTypeOperator::Subtraction], $columns);
    }

    /**
     * @param  array  $columns
     */
    public function findForTimeAddition($columns = ['*']): Collection
    {
        return $this->findWhere(['operator' => NoveltyTypeOperator::Addition], $columns);
    }

    public function whereDayType(array $dayType)
    {
        $this->model = $this->model->whereIn('apply_on_days_of_type', $dayType);

        return $this;
    }

    /**
     * @return mixed
     */
    public function whereApplicableOnAnyDayType()
    {
        $this->model = $this->model->orWhereNull('apply_on_days_of_type');

        return $this;
    }

    /**
     * @return mixed
     */
    public function whereContextType(string $context)
    {
        $this->model = $this->model->where('context_type', $context);

        return $this;
    }

    /**
     * @param  $field
     * @param  array  $columns
     * @return mixed
     */
    public function findOrWhereIn($field, array $values, $columns = ['*'])
    {
        $this->applyScope();
        $model = $this->model->orWhereIn($field, $values)->get($columns);
        $this->resetModel();

        return $this->parserResult($model);
    }

    /**
     * @todo make configurable the default novelty type for subtraction
     *
     * @return mixed
     */
    public function findDefaultForAddition()
    {
        $this->applyScope();

        $model = $this->model
            ->where([
                'operator' => NoveltyTypeOperator::Addition,
                'code' => NoveltyType::DEFAULT_FOR_ADDITION,
            ])
            ->first();

        $this->resetModel();

        return $this->parserResult($model);
    }
}
