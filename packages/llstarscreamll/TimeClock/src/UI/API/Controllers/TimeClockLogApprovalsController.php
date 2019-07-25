<?php

namespace llstarscreamll\TimeClock\UI\API\Controllers;

use Illuminate\Http\Request;
use llstarscreamll\Core\Http\Controller;
use llstarscreamll\TimeClock\Contracts\TimeClockLogRepositoryInterface;
use llstarscreamll\TimeClock\UI\API\Requests\TimeClockLogApproveRequest;

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
     * @param  TimeClockLogApproveRequest  $request
     * @param  string                      $timeClockLogId
     * @return \Illuminate\Http\Response
     */
    public function __invoke(TimeClockLogApproveRequest $request, string $timeClockLogId)
    {
        $this->timeClockLogRepository->sync($timeClockLogId, 'approvals', ['user_id' => $request->user()->id], false);

        return response()->json(['ok'], 201);
    }
}
