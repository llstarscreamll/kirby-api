<?php
namespace llstarscreamll\TimeClock\UI\API\Controllers;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use llstarscreamll\Core\Http\Controller;
use llstarscreamll\TimeClock\Actions\LogCheckInAction;
use llstarscreamll\TimeClock\Actions\LogCheckOutAction;
use llstarscreamll\TimeClock\Contracts\TimeClockLogRepositoryInterface;
use llstarscreamll\TimeClock\UI\API\Resources\TimeClockLogResource;
use llstarscreamll\Users\Contracts\IdentificationRepositoryInterface;

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
     * @var \Illuminate\Contracts\Auth\Guard
     */
    private $auth;

    /**
     * @param \Illuminate\Contracts\Auth\Guard                                    $auth
     * @param \llstarscreamll\Users\Contracts\IdentificationRepositoryInterface   $identificationRepository
     * @param \llstarscreamll\TimeClock\Contracts\TimeClockLogRepositoryInterface $timeClockLogRepository
     */
    public function __construct(
        Guard $auth,
        IdentificationRepositoryInterface $identificationRepository,
        TimeClockLogRepositoryInterface $timeClockLogRepository
    ) {
        $this->auth = $auth;
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
     * @param  \Illuminate\Http\Request    $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, LogCheckInAction $logCheckInAction, LogCheckOutAction $logCheckOutAction)
    {
        if ($request->action === 'check_in') {
            $timeClockLog = $logCheckInAction->run($this->auth->user(), $request->identification_code);
        } else {
            $timeClockLog = $logCheckOutAction->run($this->auth->user(), $request->identification_code);
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
