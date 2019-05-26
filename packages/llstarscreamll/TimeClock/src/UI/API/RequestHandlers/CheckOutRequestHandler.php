<?php
namespace llstarscreamll\TimeClock\UI\API\RequestHandlers;

use Illuminate\Http\Exceptions\HttpResponseException;
use llstarscreamll\TimeClock\Actions\LogCheckOutAction;
use llstarscreamll\TimeClock\Exceptions\MissingCheckInException;
use llstarscreamll\TimeClock\UI\API\Resources\TimeClockLogResource;
use llstarscreamll\TimeClock\UI\API\Requests\StoreTimeClockLogRequest;

/**
 * Class CheckOutRequestHandler.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CheckOutRequestHandler
{
    /**
     * @param StoreTimeClockLogRequest $request
     * @param LogCheckOutAction        $logCheckOutAction
     */
    public function __invoke(StoreTimeClockLogRequest $request, LogCheckOutAction $logCheckOutAction)
    {
        try {
            $timeClockLog = $logCheckOutAction->run($request->user(), $request->identification_code);
        } catch (MissingCheckInException $exception) {
            throw new HttpResponseException(response()->json([
                'message' => 'No hay registro de entrada',
            ], 422));
        }

        return new TimeClockLogResource($timeClockLog);
    }
}
