<?php

namespace llstarscreamll\Novelties\Repositories;

use Illuminate\Support\Collection;
use llstarscreamll\Novelties\Models\NoveltyType;
use llstarscreamll\Novelties\Enums\NoveltyTypeOperator;
use llstarscreamll\Core\Abstracts\EloquentRepositoryAbstract;
use llstarscreamll\Novelties\Contracts\NoveltyTypeRepositoryInterface;

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
     * @var array
     */
    protected $allowedIncludes = [];

    public function model(): string
    {
        return NoveltyType::class;
    }

    /**
     * @param  array        $columns
     * @return Collection
     */
    public function findForTimeSubtraction($columns = ['*']): Collection
    {
        return $this->findWhere(['operator' => NoveltyTypeOperator::Subtraction], $columns);
    }
}
