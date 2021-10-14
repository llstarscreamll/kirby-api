<?php

namespace Kirby\Production\UI\API\V1\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Kirby\Production\Jobs\ExportProductionLogsToCsvJob;
use Kirby\Production\UI\API\V1\Requests\SearchProductionLogsRequest;

class ExportProductionLogsToCsvController
{
    /**
     * @param  Request  $request
     */
    public function __invoke(SearchProductionLogsRequest $request)
    {
        ExportProductionLogsToCsvJob::dispatch($request->user(), Arr::get($request->validated(), 'filter', []));

        return response()->json(['data' => 'ok']);
    }
}
