<?php

namespace Kirby\Novelties\UI\API\V1\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Kirby\Novelties\Contracts\NoveltyRepositoryInterface;
use Kirby\Novelties\Repositories\Criteria\ByNoveltyTypeCriteria;
use Kirby\Novelties\Repositories\Criteria\ByStartDateRangeCriteria;
use Kirby\Novelties\Repositories\Criteria\CostCentersCriteria;
use Kirby\Novelties\Repositories\Criteria\EmployeeCriteria;
use Kirby\Novelties\Repositories\Criteria\HasTimeClockLogCheckOutBetweenCriteria;
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
            ->with([
                'employee.user', 'noveltyType', 'approvals:users.id,users.first_name,users.last_name',
                'subCostCenter.costCenter', 'timeClockLog:id,checked_in_at,checked_out_at',
            ]);

        if ($request->time_clock_log_check_out_start_date) {
            $novelties->pushCriteria(new HasTimeClockLogCheckOutBetweenCriteria(
                Carbon::parse($request->time_clock_log_check_out_start_date),
                Carbon::parse($request->time_clock_log_check_out_end_date)
            ));
        }

        if ($request->start_at) {
            $novelties->pushCriteria(new ByStartDateRangeCriteria(
                Carbon::parse($request->start_at['from']),
                Carbon::parse($request->start_at['to'])
            ));
        }

        if ($request->employees) {
            $novelties->pushCriteria(new EmployeeCriteria(data_get($request->employees, '*.id')));
        }

        if ($request->cost_centers) {
            $novelties->pushCriteria(new CostCentersCriteria(data_get($request->cost_centers, '*.id')));
        }

        if ($request->novelty_types) {
            $novelties->pushCriteria(new ByNoveltyTypeCriteria(data_get($request->novelty_types, '*.id')));
        }

        return NoveltyResource::collection($novelties->simplePaginate(null, ['novelties.*']));
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
            ->with(['noveltyType', 'employee.user', 'timeClockLog', 'approvals:users.id,users.first_name,users.last_name'])
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
        $noveltyData = $request->validated();

        $noveltyData['start_at'] = Carbon::parse($noveltyData['start_at']);
        $noveltyData['end_at'] = Carbon::parse($noveltyData['end_at']);

        $novelty = $this->noveltyRepository->update($noveltyData, $id);

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
