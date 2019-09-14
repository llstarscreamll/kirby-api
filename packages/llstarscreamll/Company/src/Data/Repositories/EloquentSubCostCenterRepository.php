<?php

namespace llstarscreamll\Company\Data\Repositories;

use llstarscreamll\Company\Models\SubCostCenter;
use llstarscreamll\Core\Abstracts\EloquentRepositoryAbstract;
use llstarscreamll\Company\Contracts\SubCostCenterRepositoryInterface;

/**
 * Class EloquentSubCostCenterRepository.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class EloquentSubCostCenterRepository extends EloquentRepositoryAbstract implements SubCostCenterRepositoryInterface
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

    public function model()
    {
        return SubCostCenter::class;
    }
}
