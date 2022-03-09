<?php

namespace Kirby\Novelties\UI\API\V1\Controllers;

use Kirby\Novelties\Contracts\NoveltyRepositoryInterface;
use Kirby\Novelties\UI\API\V1\Requests\CreateNoveltyApprovalRequest;
use Kirby\Novelties\UI\API\V1\Requests\DeleteNoveltyApprovalRequest;

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

    public function __construct(NoveltyRepositoryInterface $noveltyRepository)
    {
        $this->noveltyRepository = $noveltyRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(CreateNoveltyApprovalRequest $request, string $noveltyId)
    {
        $this->noveltyRepository->sync($noveltyId, 'approvals', $request->user()->id, $detachOthers = false);

        return response()->json(['ok'], 201);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(DeleteNoveltyApprovalRequest $request, string $noveltyId)
    {
        $this->noveltyRepository->deleteApproval($noveltyId, $request->user()->id);

        return response()->json(['ok'], 200);
    }
}
