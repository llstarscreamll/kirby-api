<?php

namespace Kirby\Novelties\UI\API\V1\Controllers;

use Kirby\Novelties\Contracts\NoveltyTypeRepositoryInterface;
use Kirby\Novelties\UI\API\V1\Requests\CreateNoveltyTypeRequest;
use Kirby\Novelties\UI\API\V1\Requests\DeleteNoveltyTypeRequest;
use Kirby\Novelties\UI\API\V1\Requests\GetNoveltyTypeRequest;
use Kirby\Novelties\UI\API\V1\Requests\SearchNoveltyTypesRequest;
use Kirby\Novelties\UI\API\V1\Requests\UpdateNoveltyTypeRequest;
use Kirby\Novelties\UI\API\V1\Resources\NoveltyTypeResource;
use Prettus\Repository\Criteria\RequestCriteria;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class NoveltyTypesController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class NoveltyTypesController
{
    /**
     * @var \Kirby\Novelties\Contracts\NoveltyTypeRepositoryInterface
     */
    private $noveltyTypeRepository;

    /**
     * @param  \Kirby\Novelties\Contracts\NoveltyTypeRepositoryInterface  $noveltyRepository
     */
    public function __construct(NoveltyTypeRepositoryInterface $noveltyTypeRepository)
    {
        $this->noveltyTypeRepository = $noveltyTypeRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Kirby\Novelties\UI\API\V1\Requests\SearchNoveltyTypesRequest
     * @return \Illuminate\Http\Response
     */
    public function index(SearchNoveltyTypesRequest $request)
    {
        $noveltyTypes = $this->noveltyTypeRepository
            ->pushCriteria(app(RequestCriteria::class))
            ->paginate();

        return NoveltyTypeResource::collection($noveltyTypes);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(CreateNoveltyTypeRequest $request)
    {
        $noveltyType = $this->noveltyTypeRepository->create($request->validated());

        return NoveltyTypeResource::make($noveltyType);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(GetNoveltyTypeRequest $request, $id)
    {
        return NoveltyTypeResource::make($this->noveltyTypeRepository->find($id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateNoveltyTypeRequest $request, $id)
    {
        $noveltyType = $this->noveltyTypeRepository->update($request->validated(), $id);

        return NoveltyTypeResource::make($noveltyType);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(DeleteNoveltyTypeRequest $request, $id)
    {
        $this->noveltyTypeRepository->delete($id);

        return response()->json(['data' => 'ok'], Response::HTTP_OK);
    }
}
