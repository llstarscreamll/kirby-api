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
     * @var array
     */
    protected $allowedFilters = [];

    /**
     * @var array
     */
    protected $allowedIncludes = [];

    public function model(): string
    {
        return SubCostCenter::class;
    }
}
