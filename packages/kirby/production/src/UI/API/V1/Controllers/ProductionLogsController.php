<?php

namespace Kirby\Production\UI\API\V1\Controllers;

use Illuminate\Http\Request;
use Kirby\Production\Contracts\ProductionLogRepository;
use Kirby\Production\UI\API\V1\Requests\CreateProductionLogRequest;
use Kirby\Production\UI\API\V1\Requests\SearchProductionLogsRequest;

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
        return response()->json($this->productionLogRepository->search());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Kirby\Production\UI\API\V1\Requests\CreateProductionLogRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateProductionLogRequest $request)
    {
        $productionLog = $this->productionLogRepository
            ->create(['employee_id' => $request->user()->id] + $request->validated());

        return response()->json(['data' => $productionLog]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int                         $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return response()->json(['data' => 'ok']);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request    $request
     * @param  int                         $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
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
