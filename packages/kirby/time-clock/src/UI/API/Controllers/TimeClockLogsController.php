<?php

namespace Kirby\TimeClock\UI\API\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Kirby\Core\Http\Controller;
use Kirby\Employees\Contracts\EmployeeRepositoryInterface;
use Kirby\TimeClock\Actions\LogCheckInAction;
use Kirby\TimeClock\Actions\LogCheckOutAction;
use Kirby\TimeClock\Contracts\TimeClockLogRepositoryInterface;
use Kirby\TimeClock\Events\CheckedOutEvent;
use Kirby\TimeClock\UI\API\Requests\CreateTimeClockLogRequest;
use Kirby\TimeClock\UI\API\Requests\SearchTimeClockLogsRequest;
use Kirby\TimeClock\UI\API\Resources\TimeClockLogResource;
use Prettus\Repository\Criteria\RequestCriteria;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class TimeClockLogsController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class TimeClockLogsController extends Controller
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
     * @return \Illuminate\Http\Response
     */
    public function index(SearchTimeClockLogsRequest $request)
    {
        $timeClockLogs = $this->timeClockLogRepository
            ->pushCriteria(app(RequestCriteria::class))
            ->with([
                'employee.user', 'workShift', 'novelties.noveltyType', 'subCostCenter',
                'approvals:users.id,users.first_name,users.last_name',
            ])
            ->orderBy('id', 'DESC')
            ->simplePaginate();

        return TimeClockLogResource::collection($timeClockLogs);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Kirby\TimeClock\UI\API\Requests\CreateTimeClockLogRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(
        CreateTimeClockLogRequest $request,
        EmployeeRepositoryInterface $employeeRepository,
        LogCheckInAction $logCheckInAction,
        LogCheckOutAction $logCheckOutAction
    ) {
        $timeClockLogData = $request->validated();
        $timeClockLogData['checked_in_at'] = Carbon::parse($timeClockLogData['checked_in_at']);
        $timeClockLogData['checked_out_at'] = $timeClockLogData['checked_out_at']
            ? Carbon::parse($timeClockLogData['checked_out_at'])
            : null;

        $employee = $employeeRepository
            ->with(['identifications'])
            ->find($request->employee_id);

        DB::transaction(function () use ($request, $timeClockLogData, $employee, $logCheckInAction, $logCheckOutAction) {
            Carbon::setTestNow($timeClockLogData['checked_in_at']);
            $timeClockLog = $logCheckInAction->run(
                $request->user(),
                $employee->identifications->first()->code,
                $request->work_shift_id,
                $request->check_in_novelty_type_id,
                $request->check_in_sub_cost_center_id,
            );

            if ($request->checked_out_at) {
                Carbon::setTestNow($timeClockLogData['checked_out_at']);
                $logCheckOutAction->run(
                    $request->user(),
                    $employee->identifications->first()->code,
                    $request->sub_cost_center_id,
                    $request->check_out_novelty_type_id,
                    $request->check_out_sub_cost_center_id,
                );

                event(new CheckedOutEvent($timeClockLog->id));
            }
        });

        Carbon::setTestNow();

        return response(['data' => 'ok'], Response::HTTP_CREATED);
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
