<?php

namespace llstarscreamll\Employees\UI\API\Controllers;

use Symfony\Component\HttpFoundation\Response;
use llstarscreamll\Employees\Jobs\SyncEmployeesByCsvFileJob;
use llstarscreamll\Employees\UI\API\Requests\SyncEmployeesByCsvFileRequest;

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
