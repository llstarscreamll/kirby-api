<?php

namespace llstarscreamll\Users\UI\API\Controllers;

use Symfony\Component\HttpFoundation\Response;
use llstarscreamll\Users\Jobs\SyncEmployeesByCsvFileJob;
use llstarscreamll\Users\UI\API\Requests\SyncEmployeesByCsvFileRequest;

/**
 * Class EmployeeApiController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class EmployeeApiController
{
    /**
     * @param SyncEmployeesByCsvFileRequest $request
     */
    public function syncEmployeesByCsvFile(SyncEmployeesByCsvFileRequest $request)
    {
        SyncEmployeesByCsvFileJob::dispatch($request->file('csv_file')->store('employees_sync'));

        return response()->json("", Response::HTTP_ACCEPTED);
    }
}
