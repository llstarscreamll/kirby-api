<?php
namespace llstarscreamll\TimeClock\UI\API\RequestHandlers;

use Illuminate\Http\Exceptions\HttpResponseException;
use llstarscreamll\TimeClock\Actions\LogCheckInAction;
use llstarscreamll\TimeClock\Exceptions\AlreadyCheckedInException;
use llstarscreamll\TimeClock\UI\API\Resources\TimeClockLogResource;
use llstarscreamll\TimeClock\UI\API\Requests\StoreTimeClockLogRequest;

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
            $timeClockLog = $logCheckInAction->run($request->user(), $request->identification_code);
        } catch (AlreadyCheckedInException $exception) {
            throw new HttpResponseException(response()->json([
                'message' => $exception->getMessage(),
            ], 422));
        }

        return new TimeClockLogResource($timeClockLog);
    }
}
