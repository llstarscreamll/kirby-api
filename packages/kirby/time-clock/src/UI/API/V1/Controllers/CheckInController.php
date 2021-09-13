<?php

namespace Kirby\TimeClock\UI\API\V1\Controllers;

use Illuminate\Http\Exceptions\HttpResponseException;
use Kirby\TimeClock\Actions\LogCheckIn;
use Kirby\TimeClock\Events\CheckedInEvent;
use Kirby\TimeClock\Exceptions\AlreadyCheckedInException;
use Kirby\TimeClock\Exceptions\CanNotDeductWorkShiftException;
use Kirby\TimeClock\Exceptions\InvalidNoveltyTypeException;
use Kirby\TimeClock\Exceptions\MissingSubCostCenterException;
use Kirby\TimeClock\Exceptions\TooEarlyToCheckException;
use Kirby\TimeClock\Exceptions\TooLateToCheckException;
use Kirby\TimeClock\UI\API\V1\Requests\CheckInRequest;
use Kirby\TimeClock\UI\API\V1\Resources\TimeClockLogResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CheckInController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CheckInController
{
    /**
     * @param  CheckInRequest  $request
     * @param  LogCheckIn  $logCheckInAction
     */
    public function __invoke(
        CheckInRequest $request,
        LogCheckIn $logCheckInAction
    ) {
        $errors = [];

        try {
            $timeClockLog = $logCheckInAction->run(
                $request->user(),
                $request->identification_code,
                $request->work_shift_id,
                $request->novelty_type_id,
                $request->sub_cost_center_id,
            );
        } catch (AlreadyCheckedInException $exception) {
            array_push($errors, [
                'code' => $exception->getCode(),
                'title' => 'Ya se tiene una entrada registrada.',
                'detail' => 'Ya se tiene una entrada registrada.',
            ]);
        } catch (TooEarlyToCheckException $exception) {
            array_push($errors, [
                'code' => $exception->getCode(),
                'title' => 'Es temprano para registrar la entrada.',
                'detail' => 'Si se llega temprano al turno, se debe registrar una novedad.',
                'meta' => $exception->timeClockData,
            ]);
        } catch (TooLateToCheckException $exception) {
            array_push($errors, [
                'code' => $exception->getCode(),
                'title' => 'Es tarde para registrar la entrada.',
                'detail' => 'Si se llega tarde al turno, se debe registrar una novedad.',
                'meta' => $exception->timeClockData,
            ]);
        } catch (CanNotDeductWorkShiftException $exception) {
            array_push($errors, [
                'code' => $exception->getCode(),
                'title' => 'No fue posible deducir el turno.',
                'detail' => 'No se logró deducir el turno, se debe elegir uno '
                ."de {$exception->timeClockData['work_shifts']->count()} posibles.",
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
                'detail' => 'Cuando se registra novedad que suma tiempo, se debe proveer el sub centro de costo.',
                'meta' => $exception->timeClockData,
            ]);
        }

        if ($errors) {
            throw new HttpResponseException(response()->json([
                'message' => 'No se pudo registrar la entrada',
                'errors' => $errors,
            ], Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        event(new CheckedInEvent($timeClockLog->id));

        return new TimeClockLogResource($timeClockLog);
    }
}
