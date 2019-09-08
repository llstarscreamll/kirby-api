<?php

namespace llstarscreamll\TimeClock\UI\API\Controllers;

use Illuminate\Http\Request;
use llstarscreamll\Core\Http\Controller;
use llstarscreamll\TimeClock\Events\TimeClockLogApprovalCreatedEvent;
use llstarscreamll\TimeClock\Events\TimeClockLogApprovalDeletedEvent;
use llstarscreamll\TimeClock\Contracts\TimeClockLogRepositoryInterface;
use llstarscreamll\TimeClock\UI\API\Requests\CreateTimeClockLogApprovalRequest;
use llstarscreamll\TimeClock\UI\API\Requests\DeleteTimeClockLogApprovalRequest;

/**
 * Class TimeClockLogApprovalsController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class TimeClockLogApprovalsController extends Controller
{
    /**
     * @var TimeClockLogRepositoryInterface
     */
    private $timeClockLogRepository;

    /**
     * @param TimeClockLogRepositoryInterface $timeClockLogRepository
     */
    public function __construct(TimeClockLogRepositoryInterface $timeClockLogRepository)
    {
        $this->timeClockLogRepository = $timeClockLogRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  CreateTimeClockLogApprovalRequest $request
     * @param  string                            $timeClockLogId
     * @return \Illuminate\Http\Response
     */
    public function store(CreateTimeClockLogApprovalRequest $request, string $timeClockLogId)
    {
        $this->timeClockLogRepository->sync($timeClockLogId, 'approvals', $request->user()->id, false);
        TimeClockLogApprovalCreatedEvent::dispatch($timeClockLogId, $request->user()->id);

        return response()->json(['ok'], 201);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  DeleteTimeClockLogApprovalRequest $request
     * @param  string                            $timeClockLogId
     * @return \Illuminate\Http\Response
     */
    public function destroy(DeleteTimeClockLogApprovalRequest $request, string $timeClockLogId)
    {
        $this->timeClockLogRepository->deleteApproval($timeClockLogId, $request->user()->id);
        TimeClockLogApprovalDeletedEvent::dispatch($timeClockLogId, $request->user()->id);

        return response()->json(['ok'], 200);
    }
}
