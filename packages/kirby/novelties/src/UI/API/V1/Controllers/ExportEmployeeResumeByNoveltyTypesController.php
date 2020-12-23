<?php

namespace Kirby\Novelties\UI\API\V1\Controllers;

use Kirby\Novelties\DTOs\SearchEmployeeNoveltiesData;
use Kirby\Novelties\Jobs\GenerateCsvEmployeeResumeByNoveltyTypeJob;
use Kirby\Novelties\UI\API\V1\Requests\ExportEmployeeResumeByNoveltyTypesRequest;

/**
 * Class ExportEmployeeResumeByNoveltyTypesController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class ExportEmployeeResumeByNoveltyTypesController
{
    /**
     * @param ExportEmployeeResumeByNoveltyTypesRequest $request
     */
    public function __invoke(ExportEmployeeResumeByNoveltyTypesRequest $request)
    {
        GenerateCsvEmployeeResumeByNoveltyTypeJob::dispatch(SearchEmployeeNoveltiesData::fromRequest($request));

        return ['data' => 'ok'];
    }
}
