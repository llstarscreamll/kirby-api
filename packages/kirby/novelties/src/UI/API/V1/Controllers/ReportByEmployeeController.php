<?php

namespace Kirby\Novelties\UI\API\V1\Controllers;

use Carbon\Carbon;
use Kirby\Novelties\Actions\GenerateReportByEmployee;
use Kirby\Novelties\UI\API\V1\Requests\ReportByEmployeeRequest;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ReportByEmployeeController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class ReportByEmployeeController
{
    /**
     * @param ReportByEmployeeRequest  $request
     * @param GenerateReportByEmployee $action
     */
    public function __invoke(int $employeeId, ReportByEmployeeRequest $request, GenerateReportByEmployee $action)
    {
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        return response(['data' => $action->run($employeeId, $startDate, $endDate)], Response::HTTP_OK);
    }
}
