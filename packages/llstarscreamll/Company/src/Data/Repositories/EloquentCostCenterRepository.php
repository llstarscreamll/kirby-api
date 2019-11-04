<?php

namespace llstarscreamll\Company\Data\Repositories;

use llstarscreamll\Company\Contracts\CostCenterRepositoryInterface;
use llstarscreamll\Company\Models\CostCenter;
use llstarscreamll\Core\Abstracts\EloquentRepositoryAbstract;

/**
 * Class EloquentCostCenterRepository.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class EloquentCostCenterRepository extends EloquentRepositoryAbstract implements CostCenterRepositoryInterface
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
        return CostCenter::class;
    }
}
