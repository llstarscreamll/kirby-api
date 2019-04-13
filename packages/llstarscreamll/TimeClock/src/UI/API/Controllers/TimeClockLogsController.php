<?php

namespace llstarscreamll\TimeClock\UI\API\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;
use llstarscreamll\Core\Http\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use llstarscreamll\TimeClock\UI\API\Resources\TimeClockLogResource;
use llstarscreamll\Users\Contracts\IdentificationRepositoryInterface;
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
    public function store(Request $request)
    {
        $identification = $this->identificationRepository
                               ->with(['user.workShifts'])
                               ->findByField('code', $request->identification_code)
                               ->first();

        if (! $identification) {
            throw new ModelNotFoundException();
        }

        $workShift = $identification->user->getFirstWorkShiftByClosestTime(now());

        $timeClockLog = [
            'employee_id' => $identification->user_id,
            'work_shift_id' => optional($workShift)->id,
            'checked_in_at' => now(),
            'checked_in_by_id' => $this->auth->user()->id,
        ];

        if ($request->action === 'check_out') {
            $timeClockLogUpdate = [
                'checked_out_at' => now(),
                'checked_out_by_id' => $this->auth->user()->id,
            ];

            $timeClockLog = $this->timeClockLogRepository->lastCheckInFromUserId($identification->user_id, ['id']);

            $this->timeClockLogRepository->update($timeClockLogUpdate, $timeClockLog->id);
        } else {
            $timeClockLog = $this->timeClockLogRepository->create($timeClockLog);
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
