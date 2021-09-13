<?php

namespace Kirby\Company\UI\API\V1\Controllers;

use Illuminate\Http\Request;
use Kirby\Company\Contracts\SubCostCenterRepositoryInterface;
use Kirby\Company\UI\API\V1\Resources\SubCostCenterResource;
use Prettus\Repository\Criteria\RequestCriteria;

/**
 * Class SubCostCentersController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class SubCostCentersController
{
    /**
     * @param  Request  $request
     */
    public function index(Request $request, SubCostCenterRepositoryInterface $subCostCenterRepository)
    {
        $paginatedSubCostCenters = $subCostCenterRepository
            ->pushCriteria(new RequestCriteria($request))
            ->paginate();

        return SubCostCenterResource::collection($paginatedSubCostCenters);
    }
}
