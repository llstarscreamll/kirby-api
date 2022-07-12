<?php

namespace Kirby\TimeClock\UI\API\V1\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Kirby\TimeClock\Jobs\ExportTimeClockLogsJob;
use Kirby\TimeClock\UI\API\V1\Requests\ExportTimeClockLogsRequest;

/**
 * Class ExportLogsController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class ExportLogsController
{
    public function __invoke(ExportTimeClockLogsRequest $request)
    {
        $checkedInStart = Carbon::parse($request->checkedInStart);
        $checkedInEnd = Carbon::parse($request->checkedInEnd);

        if ($checkedInStart->diffInDays($checkedInEnd) > 180) {
            throw ValidationException::withMessages(['checkedInEnd' => 'No se permite exportar más de 180 días de datos.']);
        }

        DB::enableQueryLog();
        ExportTimeClockLogsJob::dispatch($request->user()->id, $request->validated());
        logger('queries', DB::getQueryLog());

        return response()->json(['data' => 'ok']);
    }
}
