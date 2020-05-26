<?php

namespace Kirby\Employees\UI\API\V1\Controllers;

use Illuminate\Support\Arr;
use Kirby\Employees\Contracts\EmployeeRepositoryInterface;
use Kirby\Employees\Contracts\IdentificationRepositoryInterface;
use Kirby\Employees\Jobs\SyncEmployeesByCsvFileJob;
use Kirby\Employees\UI\API\V1\Requests\GetEmployeeRequest;
use Kirby\Employees\UI\API\V1\Requests\SearchEmployeesRequest;
use Kirby\Employees\UI\API\V1\Requests\SyncEmployeesByCsvFileRequest;
use Kirby\Employees\UI\API\V1\Requests\UpdateEmployeeRequest;
use Kirby\Employees\UI\API\V1\Resources\EmployeeResource;
use Kirby\Users\Contracts\UserRepositoryInterface;
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
     * @var \Kirby\Users\Contracts\UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @var \Kirby\Employees\Contracts\EmployeeRepositoryInterface
     */
    private $employeeRepository;

    /**
     * @var \Kirby\Employees\Contracts\IdentificationRepositoryInterface
     */
    private $identificationRepository;

    /**
     * @param UserRepositoryInterface           $UserRepository
     * @param EmployeeRepositoryInterface       $employeeRepository
     * @param IdentificationRepositoryInterface $identificationRepository
     */
    public function __construct(
        UserRepositoryInterface $userRepository,
        EmployeeRepositoryInterface $employeeRepository,
        IdentificationRepositoryInterface $identificationRepository
    ) {
        $this->userRepository = $userRepository;
        $this->employeeRepository = $employeeRepository;
        $this->identificationRepository = $identificationRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Kirby\Employees\UI\API\V1\Requests\SearchEmployeesRequest
     * @return \Illuminate\Http\Response
     */
    public function index(SearchEmployeesRequest $request)
    {
        $employees = $this->employeeRepository
            ->pushCriteria(app(RequestCriteria::class))
            ->with('user')
            ->orderBy('id', 'DESC')
            ->simplePaginate();

        return EmployeeResource::collection($employees);
    }

    /**
     * Display the specified resource.
     *
     * @param  \Kirby\Employees\UI\API\V1\Requests\GetEmployeeRequest
     * @return \Illuminate\Http\Response
     */
    public function show(GetEmployeeRequest $request, string $id)
    {
        $employee = $this->employeeRepository->with(['user', 'costCenter', 'workShifts', 'identifications'])->find($id);

        return new EmployeeResource($employee);
    }

    /**
     * Store the specified resource on storage.
     *
     * @param  \Kirby\Employees\UI\API\V1\Requests\UpdateEmployeeRequest
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateEmployeeRequest $request, string $id)
    {
        $employeeData = $request->validated();
        $workShiftIds = data_get($employeeData, 'work_shifts.*.id', []);
        $identifications = Arr::get($employeeData, 'identifications', []);
        $userNames = Arr::only($employeeData, ['first_name', 'last_name']);
        $employeeData['cost_center_id'] = Arr::get($employeeData, 'cost_center.id');

        $employee = $this->employeeRepository->update($employeeData, $id);

        try {
            $this->userRepository->update($userNames, $id);
            $this->employeeRepository->sync($id, 'workShifts', $workShiftIds);

            $identificationCodes = collect($identifications)
                ->map(function (array $identification) use ($id) {
                    return $this->identificationRepository->updateOrCreate(
                        ['employee_id' => $id, 'code' => $identification['code']],
                        ['employee_id' => $id] + $identification
                    );
                })
                ->pluck('code')
                ->toArray();

            $this->identificationRepository->deleteWhereEmployeeIdCodesNotIn($id, $identificationCodes);
        } catch (\Throwable $th) {
            throw $th;

            return response()->json([
                'errors' => [
                    'title' => 'Error inesperado',
                    'detail' => 'Un error inesperado ha ocurrido',
                ],
            ], 417);
        }

        return new EmployeeResource($employee);
    }
}
