<?php

namespace Kirby\Production\UI\API\V1\Controllers;

use Kirby\Employees\Models\Identification;
use Kirby\Production\Contracts\ProductionLogRepository;
use Kirby\Production\UI\API\V1\Requests\CreateProductionLogRequest;
use Kirby\Production\UI\API\V1\Requests\SearchProductionLogsRequest;
use Kirby\Production\UI\API\V1\Requests\UpdateProductionLogRequest;
use Kirby\Production\UI\API\V1\Resources\ProductionLogResource;
use Symfony\Component\HttpFoundation\Response;

class ProductionLogsController
{
    /**
     * @var \Kirby\Production\Contracts\ProductionLogRepository
     */
    private $productionLogRepository;

    public function __construct(ProductionLogRepository $productionLogRepository)
    {
        $this->productionLogRepository = $productionLogRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(SearchProductionLogsRequest $request)
    {
        return ProductionLogResource::collection($this->productionLogRepository->search());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(CreateProductionLogRequest $request)
    {
        $employeeId = $request->user()->id;

        if (!empty($request->employee_code) && $request->user()->can('production-logs.create-on-behalf-of-another-person')) {
            $employeeId = Identification::where('code', $request->get('employee_code'))->firstOrFail()->employee_id;
        }

        $productionLog = $this->productionLogRepository
            ->create(['employee_id' => $employeeId, 'tag_updated_at' => now()] + $request->validated());

        return response()->json(['data' => $productionLog->load(['machine', 'product', 'customer', 'employee'])]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $productionLog = $this->productionLogRepository->findById($id, ['*'], ['employee', 'machine', 'product', 'customer']);

        if (empty($productionLog)) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response()->json(['data' => $productionLog]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateProductionLogRequest $request, $id)
    {
        $data = $request->validated();
        $data['employee_id'] = $request->user()->id;

        if ($request->user()->can('production-logs.create-on-behalf-of-another-person') && empty($request->employee_code)) {
            return response()->json([
                'message' => 'Datos incorrectos',
                'errors' => ['employee_code' => ['El campo token de empleado es requerido']],
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($request->user()->can('production-logs.create-on-behalf-of-another-person')) {
            $data['employee_id'] = Identification::where('code', $request->get('employee_code'))->firstOrFail()->employee_id;
        }

        unset($data['employee_code']);

        $this->productionLogRepository->update($id, $data);

        return response()->json(['data' => 'ok']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        return response()->json(['data' => 'ok']);
    }
}
