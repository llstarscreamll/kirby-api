<?php

namespace llstarscreamll\TimeClock\UI\API\RequestHandlers;

use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Exceptions\HttpResponseException;
use llstarscreamll\TimeClock\Actions\LogCheckInAction;
use llstarscreamll\TimeClock\Exceptions\TooLateToCheckException;
use llstarscreamll\TimeClock\Exceptions\TooEarlyToCheckException;
use llstarscreamll\WorkShifts\UI\API\Resources\WorkShiftResource;
use llstarscreamll\Novelties\UI\API\Resources\NoveltyTypeResource;
use llstarscreamll\TimeClock\Exceptions\AlreadyCheckedInException;
use llstarscreamll\TimeClock\UI\API\Resources\TimeClockLogResource;
use llstarscreamll\TimeClock\Exceptions\InvalidNoveltyTypeException;
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
                $request->user(),
                $request->identification_code,
                $request->work_shift_id,
                $request->novelty_type
            );
        } catch (AlreadyCheckedInException $exception) {
            array_push($errors, [
                'code' => $exception->getCode(),
                'title' => $exception->getMessage(),
                'detail' => "Ya se ha registrado entrada en {$exception->checkedInAt}.",
            ]);
        } catch (TooEarlyToCheckException $exception) {
            array_push($errors, [
                'code' => $exception->getCode(),
                'title' => $exception->getMessage(),
                'detail' => 'Si se llega temprano al turno, se debe registrar una novedad.',
                'meta' => [
                    'novelty_types' => NoveltyTypeResource::collection($exception->posibleNoveltyTypes),
                ],
            ]);
        } catch (TooLateToCheckException $exception) {
            array_push($errors, [
                'code' => $exception->getCode(),
                'title' => $exception->getMessage(),
                'detail' => 'Si se llega tarde al turno, se debe registrar una novedad.',
                'meta' => [
                    'novelty_types' => NoveltyTypeResource::collection($exception->posibleNoveltyTypes),
                ],
            ]);
        } catch (CanNotDeductWorkShiftException $exception) {
            array_push($errors, [
                'code' => $exception->getCode(),
                'title' => $exception->getMessage(),
                'detail' => 'No se pudo deducir el turno, se debe elegir uno '
                ."de {$exception->posibleWorkShifts->count()} posibles.",
                'meta' => [
                    'work_shifts' => WorkShiftResource::collection($exception->posibleWorkShifts),
                ],
            ]);
        } catch (InvalidNoveltyTypeException $exception) {
            array_push($errors, [
                'code' => $exception->getCode(),
                'title' => $exception->getMessage(),
                'detail' => 'El tipo de novedad no es válido.',
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
