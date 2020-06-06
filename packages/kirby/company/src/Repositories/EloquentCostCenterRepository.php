<?php

namespace Kirby\Company\Repositories;

use Kirby\Company\Contracts\CostCenterRepositoryInterface;
use Kirby\Company\Models\CostCenter;
use Kirby\Core\Abstracts\EloquentRepositoryAbstract;

/**
 * Class EloquentCostCenterRepository.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class EloquentCostCenterRepository extends EloquentRepositoryAbstract implements CostCenterRepositoryInterface
{
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
    protected $allowedFilters = [];

    /**
     * @var array
     */
    protected $allowedIncludes = [];

    public function model(): string
    {
        return CostCenter::class;
    }
}
