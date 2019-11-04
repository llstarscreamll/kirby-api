<?php

namespace llstarscreamll\Novelties\UI\API\V1\Controllers;

use Illuminate\Http\Request;
use llstarscreamll\Novelties\Contracts\NoveltyTypeRepositoryInterface;
use llstarscreamll\Novelties\UI\API\V1\Requests\SearchNoveltyTypesRequest;
use llstarscreamll\Novelties\UI\API\V1\Resources\NoveltyTypeResource;
use Prettus\Repository\Criteria\RequestCriteria;

/**
 * Class NoveltyTypesController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class NoveltyTypesController
{
    /**
     * @var \llstarscreamll\Novelties\Contracts\NoveltyTypeRepositoryInterface
     */
    private $noveltyTypeRepository;

    /**
     * @param \llstarscreamll\Novelties\Contracts\NoveltyTypeRepositoryInterface $noveltyRepository
     */
    public function __construct(NoveltyTypeRepositoryInterface $noveltyTypeRepository)
    {
        $this->noveltyTypeRepository = $noveltyTypeRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param \llstarscreamll\Novelties\UI\API\V1\Requests\SearchNoveltyTypesRequest
     * @return \Illuminate\Http\Response
     */
    public function index(SearchNoveltyTypesRequest $request)
    {
        $noveltyTypes = $this->noveltyTypeRepository
            ->pushCriteria(app(RequestCriteria::class))
            ->simplePaginate();

        return NoveltyTypeResource::collection($noveltyTypes);
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
     * @param  int                         $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int                         $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
