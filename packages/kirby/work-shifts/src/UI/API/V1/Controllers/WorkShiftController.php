<?php

namespace Kirby\WorkShifts\UI\API\V1\Controllers;

use Illuminate\Http\Request;
use Kirby\WorkShifts\Contracts\WorkShiftRepositoryInterface;
use Kirby\WorkShifts\UI\API\V1\Requests\CreateWorkShiftRequest;
use Kirby\WorkShifts\UI\API\V1\Requests\DeleteWorkShiftRequest;
use Kirby\WorkShifts\UI\API\V1\Requests\ShowWorkShiftRequest;
use Kirby\WorkShifts\UI\API\V1\Requests\UpdateWorkShiftRequest;
use Kirby\WorkShifts\UI\API\V1\Resources\WorkShiftResource;

/**
 * Class WorkShiftController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class WorkShiftController
{
    /**
     * @var WorkShiftRepository
     */
    private $workShiftRepository;

    /**
     * @param  \Kirby\WorkShifts\Contracts\WorkShiftRepositoryInterface  $workShiftRepository
     */
    public function __construct(WorkShiftRepositoryInterface $workShiftRepository)
    {
        $this->workShiftRepository = $workShiftRepository;
    }

    /**
     * @return Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $workShift = $this->workShiftRepository->search()->paginate();

        return WorkShiftResource::collection($workShift);
    }

    /**
     * @param  \Kirby\WorkShifts\UI\API\V1\Requests\CreateWorkShiftRequest  $request
     * @return Illuminate\Http\Response
     */
    public function store(CreateWorkShiftRequest $request)
    {
        $workShiftData = $request->validated();
        $workShift = $this->workShiftRepository->create($workShiftData);

        return new WorkShiftResource($workShift);
    }

    /**
     * @param  \Kirby\WorkShifts\UI\API\V1\Requests\ShowWorkShiftRequest  $request
     * @param  int  $workShiftId
     */
    public function show(ShowWorkShiftRequest $request, $workShiftId)
    {
        $workShift = $this->workShiftRepository->find($workShiftId);

        return new WorkShiftResource($workShift);
    }

    /**
     * @param  \Kirby\WorkShifts\UI\API\V1\Requests\UpdateWorkShiftRequest  $request
     * @param  int  $workShiftId
     */
    public function update(UpdateWorkShiftRequest $request, $workShiftId)
    {
        $workShift = $this->workShiftRepository->update($request->validated(), $workShiftId);

        return new WorkShiftResource($workShift);
    }

    /**
     * @param $workShiftId
     */
    public function destroy(DeleteWorkShiftRequest $request, $workShiftId)
    {
        $this->workShiftRepository->delete($workShiftId);

        return response()->json('', 204);
    }
}
