<?php

namespace Kirby\Novelties\UI\API\V1\Controllers;

use Illuminate\Http\Request;
use Kirby\Novelties\Contracts\NoveltyRepositoryInterface;
use Kirby\Novelties\UI\API\V1\Requests\DeleteNoveltyRequest;
use Kirby\Novelties\UI\API\V1\Requests\GetNoveltyRequest;
use Kirby\Novelties\UI\API\V1\Requests\SearchNoveltiesRequest;
use Kirby\Novelties\UI\API\V1\Requests\UpdateNoveltyRequest;
use Kirby\Novelties\UI\API\V1\Resources\NoveltyResource;
use Prettus\Repository\Criteria\RequestCriteria;

/**
 * Class NoveltiesController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class NoveltiesController
{
    /**
     * @var \Kirby\Novelties\Contracts\NoveltyRepositoryInterface
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
     * @param  \Kirby\Novelties\UI\API\V1\Requests\SearchNoveltiesRequest $request
     * @return \Illuminate\Http\Response
     */
    public function index(SearchNoveltiesRequest $request)
    {
        $novelties = $this->noveltyRepository
            ->pushCriteria(app(RequestCriteria::class))
            ->with(['employee.user', 'noveltyType', 'approvals:users.id,users.first_name,users.last_name'])
            ->orderBy('id', 'DESC')
            ->simplePaginate();

        return NoveltyResource::collection($novelties);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request    $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \Kirby\Novelties\UI\API\V1\Requests\GetNoveltyRequest $request
     * @param  int                                                   $id
     * @return \Illuminate\Http\Response
     */
    public function show(GetNoveltyRequest $request, $id)
    {
        $novelty = $this->noveltyRepository
            ->with(['noveltyType', 'employee.user', 'timeClockLog'])
            ->find($id);

        return NoveltyResource::make($novelty);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Kirby\Novelties\UI\API\V1\Requests\UpdateNoveltyRequest $request
     * @param  int                                                      $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateNoveltyRequest $request, $id)
    {
        $novelty = $this->noveltyRepository->update($request->validated(), $id);

        return new NoveltyResource($novelty);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int                         $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(DeleteNoveltyRequest $request, $id)
    {
        $this->noveltyRepository->delete($id);

        return response()->json(['data' => 'ok', 200]);
    }
}
