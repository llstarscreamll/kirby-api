<?php

namespace llstarscreamll\TimeClock\UI\API\RequestHandlers;

use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Exceptions\HttpResponseException;
use llstarscreamll\TimeClock\Actions\LogCheckInAction;
use llstarscreamll\TimeClock\Exceptions\AlreadyCheckedInException;
use llstarscreamll\TimeClock\UI\API\Resources\TimeClockLogResource;
use llstarscreamll\TimeClock\UI\API\Requests\StoreTimeClockLogRequest;
use llstarscreamll\TimeClock\Exceptions\CanNotDeductWorkShiftException;

/**
 * Class CheckInRequestHandler.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CheckInRequestHandler
{
    /**
     * @param StoreTimeClockLogRequest $request
     * @param LogCheckInAction         $logCheckInAction
     */
    public function __invoke(StoreTimeClockLogRequest $request, LogCheckInAction $logCheckInAction)
    {
        try {
            $timeClockLog = $logCheckInAction->run($request->user(), $request->identification_code, $request->work_shift_id);
        } catch (CanNotDeductWorkShiftException | AlreadyCheckedInException $exception) {
            throw new HttpResponseException(response()->json([
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        return new TimeClockLogResource($timeClockLog);
    }
}
