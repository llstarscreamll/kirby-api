<?php

namespace Kirby\Employees\UI\API\Controllers;

use Symfony\Component\HttpFoundation\Response;
use Prettus\Repository\Criteria\RequestCriteria;
use Kirby\Employees\Jobs\SyncEmployeesByCsvFileJob;
use Kirby\Employees\UI\API\Resources\EmployeeResource;
use Kirby\Employees\Contracts\EmployeeRepositoryInterface;
use Kirby\Employees\UI\API\Requests\SearchEmployeesRequest;
use Kirby\Employees\UI\API\Requests\SyncEmployeesByCsvFileRequest;

/**
 * Class EmployeeApiController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class EmployeeApiController
{
    /**
     * @var \Kirby\Employees\Contracts\EmployeeRepositoryInterface
     */
    private $employeeRepository;

    /**
     * @param \Kirby\Employees\Contracts\EmployeeRepositoryInterface $noveltyRepository
     */
    public function __construct(EmployeeRepositoryInterface $employeeRepository)
    {
        $this->employeeRepository = $employeeRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Kirby\Novelties\UI\API\V1\Requests\SearchNoveltyTypesRequest
     * @return \Illuminate\Http\Response
     */
    public function index(SearchEmployeesRequest $request)
    {
        $noveltyTypes = $this->employeeRepository
            ->pushCriteria(app(RequestCriteria::class))
            ->with('user')
            ->orderBy('id', 'DESC')
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
