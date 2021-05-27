<?php

namespace Kirby\Production\UI\API\V1\Controllers;

use Illuminate\Http\Request;
use Kirby\Production\Jobs\ExportProductionLogsToCsvJob;
use Kirby\Production\UI\API\V1\Requests\ExportProductionLogsToCsvRequest;

class ExportProductionLogsToCsvController
{
    /**
     * @param Request $request
     */
    public function __invoke(ExportProductionLogsToCsvRequest $request)
    {
        ExportProductionLogsToCsvJob::dispatch($request->user(), $request->validated());

        return response()->json(['data' => 'ok']);
    }
}
