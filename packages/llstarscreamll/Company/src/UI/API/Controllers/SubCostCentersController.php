<?php

namespace llstarscreamll\Company\UI\API\Controllers;

use Illuminate\Http\Request;
use Prettus\Repository\Criteria\RequestCriteria;
use llstarscreamll\Company\UI\API\Resources\SubCostCenterResource;
use llstarscreamll\Company\Contracts\SubCostCenterRepositoryInterface;

/**
 * Class SubCostCentersController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SubCostCentersController
{
    /**
     * @param Request $request
     */
    public function index(Request $request, SubCostCenterRepositoryInterface $subCostCenterRepository)
    {
        $paginatedSubCostCenters = $subCostCenterRepository
            ->pushCriteria(new RequestCriteria($request))
            ->paginate();

        return SubCostCenterResource::collection($paginatedSubCostCenters);
    }
}
