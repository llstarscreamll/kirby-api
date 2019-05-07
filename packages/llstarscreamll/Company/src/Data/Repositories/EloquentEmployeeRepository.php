<?php

namespace llstarscreamll\Company\Data\Repositories;

use llstarscreamll\Company\Models\CostCenter;
use llstarscreamll\Core\Abstracts\EloquentRepositoryAbstract;
use llstarscreamll\Company\Contracts\CostCenterRepositoryInterface;

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
    protected $allowedFilters = ['code', 'identification_number'];

    /**
     * @var array
     */
    protected $allowedIncludes = [];

    public function model(): string
    {
        return CostCenter::class;
    }
}
