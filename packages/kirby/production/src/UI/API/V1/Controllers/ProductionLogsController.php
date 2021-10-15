<?php

namespace Kirby\Production\UI\API\V1\Controllers;

use Kirby\Production\Contracts\ProductionLogRepository;
use Kirby\Production\UI\API\V1\Requests\CreateProductionLogRequest;
use Kirby\Production\UI\API\V1\Requests\SearchProductionLogsRequest;
use Kirby\Production\UI\API\V1\Requests\UpdateProductionLogRequest;
use Kirby\Production\UI\API\V1\Resources\ProductionLogResource;

class ProductionLogsController
{
    /**
     * @var \Kirby\Production\Contracts\ProductionLogRepository
     */
    private $productionLogRepository;

    /**
     * @param \Kirby\Production\Contracts\ProductionLogRepository $productionLogRepository
     */
    public function __construct(ProductionLogRepository $productionLogRepository)
    {
        $this->productionLogRepository = $productionLogRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Kirby\Production\UI\API\V1\Requests\SearchProductionLogsRequest $request
     * @return \Illuminate\Http\Response
     */
    public function index(SearchProductionLogsRequest $request)
    {
        return ProductionLogResource::collection($this->productionLogRepository->search());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Kirby\Production\UI\API\V1\Requests\CreateProductionLogRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateProductionLogRequest $request)
    {
        $currentUserId = $request->user()->id;

        $employeeId = $request->user()->can('production-logs.create-on-behalf-of-another-person')
            ? $request->get('employee_id', $currentUserId)
            : $currentUserId;

        $productionLog = $this->productionLogRepository
            ->create(['employee_id' => $employeeId] + $request->validated());

        return response()->json(['data' => $productionLog->load(['machine', 'product', 'customer', 'employee'])]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int                         $id
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
     * @param  \Kirby\Production\UI\API\V1\Requests\UpdateProductionLogRequest $request
     * @param  int                                                             $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateProductionLogRequest $request, $id)
    {
        $log = $this->productionLogRepository->update($id, $request->validated());

        return response()->json(['data' => 'ok']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int                         $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        return response()->json(['data' => 'ok']);
    }
}
