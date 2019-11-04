<?php

namespace llstarscreamll\Employees\UI\API\Controllers;

use llstarscreamll\Employees\Contracts\EmployeeRepositoryInterface;
use llstarscreamll\Employees\Jobs\SyncEmployeesByCsvFileJob;
use llstarscreamll\Employees\UI\API\Requests\SearchEmployeesRequest;
use llstarscreamll\Employees\UI\API\Requests\SyncEmployeesByCsvFileRequest;
use llstarscreamll\Employees\UI\API\Resources\EmployeeResource;
use Prettus\Repository\Criteria\RequestCriteria;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class EmployeeApiController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class EmployeeApiController
{
    /**
     * @var \llstarscreamll\Employees\Contracts\EmployeeRepositoryInterface
     */
    private $employeeRepository;

    /**
     * @param \llstarscreamll\Employees\Contracts\EmployeeRepositoryInterface $noveltyRepository
     */
    public function __construct(EmployeeRepositoryInterface $employeeRepository)
    {
        $this->employeeRepository = $employeeRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param \llstarscreamll\Novelties\UI\API\V1\Requests\SearchNoveltyTypesRequest
     * @return \Illuminate\Http\Response
     */
    public function index(SearchEmployeesRequest $request)
    {
        $noveltyTypes = $this->employeeRepository
            ->pushCriteria(app(RequestCriteria::class))
            ->with('user')
            ->simplePaginate();

        return EmployeeResource::collection($noveltyTypes);
    }

    /**
     * @param SyncEmployeesByCsvFileRequest $request
     */
    public function syncEmployeesByCsvFile(SyncEmployeesByCsvFileRequest $request)
    {
        SyncEmployeesByCsvFileJob::dispatch($request->user()->id, $request->file('csv_file')->store('employees_sync'));

        return response()->json([''], Response::HTTP_ACCEPTED);
    }
}
