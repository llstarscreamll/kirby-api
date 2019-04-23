<?php

namespace llstarscreamll\TimeClock\UI\API\Controllers;

use Illuminate\Http\Request;
use llstarscreamll\Core\Http\Controller;
use Illuminate\Http\Exceptions\HttpResponseException;
use llstarscreamll\TimeClock\Actions\LogCheckInAction;
use llstarscreamll\TimeClock\Actions\LogCheckOutAction;
use llstarscreamll\TimeClock\Exceptions\MissingCheckInException;
use llstarscreamll\TimeClock\UI\API\Resources\TimeClockLogResource;
use llstarscreamll\Users\Contracts\IdentificationRepositoryInterface;
use llstarscreamll\TimeClock\UI\API\Requests\StoreTimeClockLogRequest;
use llstarscreamll\TimeClock\Contracts\TimeClockLogRepositoryInterface;

/**
 * Class TimeClockLogsController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class TimeClockLogsController extends Controller
{
    /**
     * @var \llstarscreamll\Users\Contracts\IdentificationRepositoryInterface
     */
    private $identificationRepository;

    /**
     * @var \llstarscreamll\TimeClock\Contracts\TimeClockLogRepositoryInterface
     */
    private $timeClockLogRepository;

    /**
     * @param \llstarscreamll\Users\Contracts\IdentificationRepositoryInterface   $identificationRepository
     * @param \llstarscreamll\TimeClock\Contracts\TimeClockLogRepositoryInterface $timeClockLogRepository
     */
    public function __construct(
        IdentificationRepositoryInterface $identificationRepository,
        TimeClockLogRepositoryInterface $timeClockLogRepository
    ) {
        $this->identificationRepository = $identificationRepository;
        $this->timeClockLogRepository = $timeClockLogRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \llstarscreamll\TimeClock\UI\API\Requests\StoreTimeClockLogRequest $request
     * @param \llstarscreamll\TimeClock\Actions\LogCheckInAction                 $logCheckInAction
     * @param \llstarscreamll\TimeClock\Actions\LogCheckOutAction                $logCheckOutAction
     */
    public function store(StoreTimeClockLogRequest $request, LogCheckInAction $logCheckInAction, LogCheckOutAction $logCheckOutAction)
    {
        try {
            $timeClockLog = $request->action === 'check_in'
                ? $logCheckInAction->run($request->user(), $request->identification_code)
                : $logCheckOutAction->run($request->user(), $request->identification_code);
        } catch (MissingCheckInException $exception) {
            throw new HttpResponseException(response()->json([
                'message' => 'No hay registro de entrada',
            ], 422));
        }

        return new TimeClockLogResource($timeClockLog);
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
