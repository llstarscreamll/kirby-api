<?php

namespace llstarscreamll\TimeClock\UI\API\RequestHandlers;

use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Exceptions\HttpResponseException;
use llstarscreamll\TimeClock\Actions\LogCheckInAction;
use llstarscreamll\WorkShifts\UI\API\Resources\WorkShiftResource;
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
        $errors = [];

        try {
            $timeClockLog = $logCheckInAction->run(
                $request->user(), $request->identification_code, $request->work_shift_id
            );
        } catch (AlreadyCheckedInException $exception) {
            array_push($errors, [
                'code' => $exception->getCode(),
                'title' => $exception->getMessage(),
                'detail' => "Ya se ha registrado entrada en {$exception->checkedInAt}.",
            ]);
        } catch (CanNotDeductWorkShiftException $exception) {
            array_push($errors, [
                'code' => $exception->getCode(),
                'title' => $exception->getMessage(),
                'detail' => 'No se pudo deducir el turno, debes elegir uno '
                ."de {$exception->posibleWorkShifts->count()} posibles.",
                'meta' => [
                    'work_shifts' => WorkShiftResource::collection($exception->posibleWorkShifts),
                ],
            ]);
        }

        if ($errors) {
            throw new HttpResponseException(response()->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        return new TimeClockLogResource($timeClockLog);
    }
}
