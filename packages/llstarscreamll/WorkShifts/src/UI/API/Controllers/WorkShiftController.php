<?php
namespace llstarscreamll\WorkShifts\UI\API\Controllers;

use Illuminate\Http\Response;
use llstarscreamll\Core\Http\Controller;
use llstarscreamll\WorkShifts\Contracts\WorkShiftRepositoryInterface;
use llstarscreamll\WorkShifts\UI\API\Requests\CreateWorkShiftRequest;
use llstarscreamll\WorkShifts\UI\API\Resources\WorkShiftResource;

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

    public function index()
    {
        //
    }

    /**
     * @param  \llstarscreamll\WorkShifts\UI\API\Requests\CreateWorkShiftRequest $request
     * @return Illuminate\Http\Response
     */
    public function store(CreateWorkShiftRequest $request)
    {
        $workShiftData = $request->validated();
        $workShift     = $this->workShiftRepository->create($workShiftData);

        return new WorkShiftResource($workShift);
    }

    public function show()
    {
        //
    }

    public function update()
    {
        //
    }

    public function destroy()
    {
        //
    }

}
