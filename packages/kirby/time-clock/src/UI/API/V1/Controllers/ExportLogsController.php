<?php

namespace Kirby\TimeClock\UI\API\V1\Controllers;

use Carbon\Carbon;
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

        if ($checkedInStart->diffInMonths($checkedInEnd) > 6) {
            throw ValidationException::withMessages(['checkedInEnd' => 'No se permite exportar más de 6 meses de datos.']);
        }

        ExportTimeClockLogsJob::dispatch($request->user()->id, $request->validated());

        return response()->json(['data' => 'ok']);
    }
}
