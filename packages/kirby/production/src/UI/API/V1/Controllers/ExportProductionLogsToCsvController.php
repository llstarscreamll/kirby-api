<?php

namespace Kirby\Production\UI\API\V1\Controllers;

use Illuminate\Http\Request;
use Kirby\Production\Jobs\ExportProductionLogsToCsvJob;
use Kirby\Production\UI\API\V1\Requests\SearchProductionLogsRequest;

class ExportProductionLogsToCsvController
{
    /**
     * @param  Request  $request
     */
    public function __invoke(SearchProductionLogsRequest $request)
    {
        ExportProductionLogsToCsvJob::dispatch($request->user(), $request->validated());

        return response()->json(['data' => 'ok']);
    }
}
