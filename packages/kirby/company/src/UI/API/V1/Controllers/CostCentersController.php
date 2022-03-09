<?php

namespace Kirby\Company\UI\API\V1\Controllers;

use Illuminate\Http\Request;
use Kirby\Company\Contracts\CostCenterRepositoryInterface;
use Kirby\Company\UI\API\V1\Resources\CostCenterResource;
use Prettus\Repository\Criteria\RequestCriteria;

/**
 * Class CostCentersController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CostCentersController
{
    public function index(Request $request, CostCenterRepositoryInterface $costCenterRepository)
    {
        $paginatedSubCostCenters = $costCenterRepository
            ->pushCriteria(new RequestCriteria($request))
            ->paginate();

        return CostCenterResource::collection($paginatedSubCostCenters);
    }
}
