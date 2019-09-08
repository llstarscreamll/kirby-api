<?php

namespace llstarscreamll\Novelties\UI\API\V1\Controllers;

use llstarscreamll\Novelties\Contracts\NoveltyRepositoryInterface;
use llstarscreamll\Novelties\UI\API\V1\Requests\CreateNoveltyApprovalRequest;
use llstarscreamll\TimeClock\UI\API\Requests\DeleteTimeClockLogApprovalRequest;

/**
 * Class NoveltyApprovalsController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class NoveltyApprovalsController
{
    /**
     * @var NoveltyRepositoryInterface
     */
    private $noveltyRepository;

    /**
     * @param NoveltyRepositoryInterface $noveltyRepository
     */
    public function __construct(NoveltyRepositoryInterface $noveltyRepository)
    {
        $this->noveltyRepository = $noveltyRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  CreateNoveltyApprovalRequest $request
     * @param  string                       $timeClockLogId
     * @return \Illuminate\Http\Response
     */
    public function store(CreateNoveltyApprovalRequest $request, string $timeClockLogId)
    {
        $this->noveltyRepository->sync($timeClockLogId, 'approvals', $request->user()->id, false);

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
        $this->noveltyRepository->deleteApproval($timeClockLogId, $request->user()->id);

        return response()->json(['ok'], 200);
    }
}
