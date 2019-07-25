<?php

namespace llstarscreamll\TimeClock\UI\API\Controllers;

use Symfony\Component\HttpFoundation\Response;
use llstarscreamll\TimeClock\Events\CheckedOutEvent;
use Illuminate\Http\Exceptions\HttpResponseException;
use llstarscreamll\TimeClock\Actions\LogCheckOutAction;
use llstarscreamll\TimeClock\UI\API\Requests\CheckOutRequest;
use llstarscreamll\TimeClock\Exceptions\MissingCheckInException;
use llstarscreamll\TimeClock\Exceptions\TooLateToCheckException;
use llstarscreamll\TimeClock\Exceptions\TooEarlyToCheckException;
use llstarscreamll\TimeClock\UI\API\Resources\TimeClockLogResource;
use llstarscreamll\TimeClock\Exceptions\InvalidNoveltyTypeException;
use llstarscreamll\TimeClock\Exceptions\MissingSubCostCenterException;

/**
 * Class CheckOutController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CheckOutController
{
    /**
     * @param CheckOutRequest   $request
     * @param LogCheckOutAction $logCheckOutAction
     */
    public function __invoke(
        CheckOutRequest $request,
        LogCheckOutAction $logCheckOutAction
    ) {
        $errors = [];

        try {
            $timeClockLog = $logCheckOutAction->run(
                $request->user(),
                $request->identification_code,
                $request->sub_cost_center_id,
                $request->novelty_type_id,
                $request->novelty_sub_cost_center_id
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
                'meta' => $exception->timeClockData,
            ]);
        } catch (TooLateToCheckException $exception) {
            array_push($errors, [
                'code' => $exception->getCode(),
                'title' => 'Es tarde para registrar la salida.',
                'detail' => 'Si se sale tarde del turno, se debe registrar un tipo de novedad.',
                'meta' => $exception->timeClockData,
            ]);
        } catch (InvalidNoveltyTypeException $exception) {
            array_push($errors, [
                'code' => $exception->getCode(),
                'title' => 'Tipo de novedad no válido.',
                'detail' => 'El tipo de novedad no es válido.',
                'meta' => $exception->timeClockData,
            ]);
        } catch (MissingSubCostCenterException $exception) {
            array_push($errors, [
                'code' => $exception->getCode(),
                'title' => 'Datos inválidos.',
                'detail' => 'Sub centro de costo es un campo obligatorio.',
                'meta' => $exception->timeClockData,
            ]);
        }

        if ($errors) {
            throw new HttpResponseException(response()->json([
                'message' => 'No se pudo registrar la salida',
                'errors' => $errors,
            ], Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        event(new CheckedOutEvent($timeClockLog->id));

        return new TimeClockLogResource($timeClockLog);
    }
}
