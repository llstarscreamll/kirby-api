<?php

namespace Kirby\TimeClock\UI\API\V1\Controllers;

use Kirby\TimeClock\Jobs\ExportTimeClockLogsJob;
use Kirby\TimeClock\UI\API\V1\Requests\SearchTimeClockLogsRequest;

/**
 * Class ExportLogsController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class ExportLogsController
{
    public function __invoke(SearchTimeClockLogsRequest $request)
    {
        if (! $request->user()->can('time-clock-logs.global-search')) {
            return abort(403);
        }

        ExportTimeClockLogsJob::dispatch($request->user()->id, $request->validated());

        return response()->json(['data' => 'ok']);
    }
}
