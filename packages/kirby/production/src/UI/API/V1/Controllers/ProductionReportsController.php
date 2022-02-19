<?php

namespace Kirby\Production\UI\API\V1\Controllers;

use Kirby\Production\Contracts\ProductionReportRepository;
use Kirby\Production\UI\API\V1\Requests\SearchProductionLogsRequest;
use Symfony\Component\HttpFoundation\Response;

class ProductionReportsController
{
    public function __invoke(SearchProductionLogsRequest $request, ProductionReportRepository $productionReportRepository)
    {
        return response(
            ['data' => $productionReportRepository->getKilogramsAcummulatedByProduct($request->validated())],
            Response::HTTP_OK
        );
    }
}
