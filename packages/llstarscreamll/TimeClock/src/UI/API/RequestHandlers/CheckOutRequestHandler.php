<?php

namespace llstarscreamll\TimeClock\UI\API\RequestHandlers;

use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Exceptions\HttpResponseException;
use llstarscreamll\TimeClock\Actions\LogCheckOutAction;
use llstarscreamll\TimeClock\Exceptions\MissingCheckInException;
use llstarscreamll\TimeClock\Exceptions\TooEarlyToCheckException;
use llstarscreamll\Novelties\UI\API\Resources\NoveltyTypeResource;
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
        $errors = [];

        try {
            $timeClockLog = $logCheckOutAction->run($request->user(), $request->identification_code);
        } catch (MissingCheckInException $exception) {
            array_push($errors, [
                'code' => $exception->getCode(),
                'title' => $exception->getMessage(),
                'detail' => 'No se puede registrar salida si no hay registro de entrada.',
            ]);
        } catch (TooEarlyToCheckException $exception) {
            array_push($errors, [
                'code' => $exception->getCode(),
                'title' => $exception->getMessage(),
                'detail' => 'Si se sale temprano del turno, se debe registrar un tipo de novedad.',
                'meta' => [
                    'novelty_types' => NoveltyTypeResource::collection($exception->posibleNoveltyTypes),
                ],
            ]);
        }

        if ($errors) {
            throw new HttpResponseException(response()->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        return new TimeClockLogResource($timeClockLog);
    }
}
