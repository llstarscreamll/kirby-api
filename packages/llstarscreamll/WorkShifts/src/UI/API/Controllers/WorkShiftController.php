<?php

namespace llstarscreamll\WorkShifts\UI\API\Controllers;

use llstarscreamll\Core\Http\Controller;
use llstarscreamll\WorkShifts\UI\API\Resources\WorkShiftResource;
use llstarscreamll\WorkShifts\UI\API\Requests\ShowWorkShiftRequest;
use llstarscreamll\WorkShifts\Contracts\WorkShiftRepositoryInterface;
use llstarscreamll\WorkShifts\UI\API\Requests\CreateWorkShiftRequest;
use llstarscreamll\WorkShifts\UI\API\Requests\DeleteWorkShiftRequest;
use llstarscreamll\WorkShifts\UI\API\Requests\UpdateWorkShiftRequest;

/**
 * Class WorkShiftController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class WorkShiftController extends Controller
{
    /**
     * @var WorkShiftRepository
     */
    private $workShiftRepository;

    /**
     * @param \llstarscreamll\WorkShifts\Contracts\WorkShiftRepositoryInterface $workShiftRepository
     */
    public function __construct(WorkShiftRepositoryInterface $workShiftRepository)
    {
        $this->workShiftRepository = $workShiftRepository;
    }

    /**
     * @return Illuminate\Http\Response
     */
    public function index()
    {
        $workShift = $this->workShiftRepository->search()->paginate();

        return WorkShiftResource::collection($workShift);
    }

    /**
     * @param  \llstarscreamll\WorkShifts\UI\API\Requests\CreateWorkShiftRequest $request
     * @return Illuminate\Http\Response
     */
    public function store(CreateWorkShiftRequest $request)
    {
        $workShiftData = $request->validated();
        $workShift = $this->workShiftRepository->create($workShiftData);

        return new WorkShiftResource($workShift);
    }

    /**
     * @param \llstarscreamll\WorkShifts\UI\API\Requests\ShowWorkShiftRequest $request
     * @param int                                                             $workShiftId
     */
    public function show(ShowWorkShiftRequest $request, $workShiftId)
    {
        $workShift = $this->workShiftRepository->find($workShiftId);

        return new WorkShiftResource($workShift);
    }

    /**
     * @param \llstarscreamll\WorkShifts\UI\API\Requests\UpdateWorkShiftRequest $request
     * @param int                                                               $workShiftId
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
