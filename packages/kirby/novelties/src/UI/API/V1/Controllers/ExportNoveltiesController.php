<?php

namespace Kirby\Novelties\UI\API\V1\Controllers;

use Kirby\Novelties\Jobs\GenerateCsvReportByEmployeeJob;
use Kirby\Novelties\UI\API\V1\Requests\ExportNoveltiesRequest;

/**
 * Class ExportNoveltiesController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class ExportNoveltiesController
{
    /**
     * @param  ExportNoveltiesRequest  $request
     */
    public function __invoke(ExportNoveltiesRequest $request)
    {
        GenerateCsvReportByEmployeeJob::dispatch($request->user()->id, $request->validated());

        return response()->json(['data' => 'ok']);
    }
}
