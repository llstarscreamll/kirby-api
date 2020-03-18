<?php

namespace Kirby\Company\Repositories;

use Kirby\Company\Contracts\SubCostCenterRepositoryInterface;
use Kirby\Company\Models\SubCostCenter;
use Kirby\Core\Abstracts\EloquentRepositoryAbstract;

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
