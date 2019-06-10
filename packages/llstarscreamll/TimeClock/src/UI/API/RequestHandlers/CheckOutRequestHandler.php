<?php

namespace llstarscreamll\TimeClock\UI\API\RequestHandlers;

use Symfony\Component\HttpFoundation\Response;
use llstarscreamll\TimeClock\Events\CheckedOutEvent;
use Illuminate\Http\Exceptions\HttpResponseException;
use llstarscreamll\TimeClock\Actions\LogCheckOutAction;
use llstarscreamll\TimeClock\Exceptions\MissingCheckInException;
use llstarscreamll\TimeClock\Exceptions\TooLateToCheckException;
use llstarscreamll\TimeClock\Exceptions\TooEarlyToCheckException;
use llstarscreamll\Novelties\UI\API\Resources\NoveltyTypeResource;
use llstarscreamll\TimeClock\UI\API\Resources\TimeClockLogResource;
use llstarscreamll\TimeClock\Exceptions\InvalidNoveltyTypeException;
use llstarscreamll\Novelties\Contracts\NoveltyTypeRepositoryInterface;
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
    public function __invoke(
        StoreTimeClockLogRequest $request,
        LogCheckOutAction $logCheckOutAction,
        NoveltyTypeRepositoryInterface $noveltyTypeRepository
    ) {
        $errors = [];

        try {
            $timeClockLog = $logCheckOutAction->run(
                $request->user(),
                $request->identification_code,
                $request->novelty_type
            );
        } catch (MissingCheckInException $exception) {
            array_push($errors, [
                'code' => $exception->getCode(),
                'title' => 'No se ha registrado entrada.',
                'detail' => 'No se puede registrar salida si no hay registro de entrada.',
            ]);
        } catch (TooEarlyToCheckException $exception) {
            array_push($errors, [
                'code' => $exception->getCode(),
                'title' => 'Es temprano para registrar la salida.',
                'detail' => 'Si se sale temprano del turno, se debe registrar un tipo de novedad.',
                'meta' => [
                    'novelty_types' => NoveltyTypeResource::collection($noveltyTypeRepository->findForTimeSubtraction()),
                ],
            ]);
        } catch (TooLateToCheckException $exception) {
            array_push($errors, [
                'code' => $exception->getCode(),
                'title' => 'Es tarde para registrar la salida.',
                'detail' => 'Si se sale tarde del turno, se debe registrar un tipo de novedad.',
                'meta' => [
                    'novelty_types' => NoveltyTypeResource::collection($noveltyTypeRepository->findForTimeAddition()),
                ],
            ]);
        } catch (InvalidNoveltyTypeException $exception) {
            array_push($errors, [
                'code' => $exception->getCode(),
                'title' => 'Tipo de novedad no válido.',
                'detail' => 'El tipo de novedad no es válido.',
                'meta' => [
                    'novelty_types' => NoveltyTypeResource::collection(
                        $exception->punctuality > 0
                            ? $noveltyTypeRepository->findForTimeAddition()
                            : $noveltyTypeRepository->findForTimeSubtraction()
                    ),
                ],
            ]);
        }

        if ($errors) {
            throw new HttpResponseException(response()->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        event(new CheckedOutEvent($timeClockLog->id));

        return new TimeClockLogResource($timeClockLog);
    }
}
