<?php

namespace llstarscreamll\TimeClock\UI\API\RequestHandlers;

use Symfony\Component\HttpFoundation\Response;
use llstarscreamll\TimeClock\Events\CheckedInEvent;
use Illuminate\Http\Exceptions\HttpResponseException;
use llstarscreamll\TimeClock\Actions\LogCheckInAction;
use llstarscreamll\TimeClock\UI\API\Requests\CheckInRequest;
use llstarscreamll\TimeClock\Exceptions\TooLateToCheckException;
use llstarscreamll\TimeClock\Exceptions\TooEarlyToCheckException;
use llstarscreamll\WorkShifts\UI\API\Resources\WorkShiftResource;
use llstarscreamll\Novelties\UI\API\Resources\NoveltyTypeResource;
use llstarscreamll\TimeClock\Exceptions\AlreadyCheckedInException;
use llstarscreamll\TimeClock\UI\API\Resources\TimeClockLogResource;
use llstarscreamll\TimeClock\Exceptions\InvalidNoveltyTypeException;
use llstarscreamll\Novelties\Contracts\NoveltyTypeRepositoryInterface;
use llstarscreamll\TimeClock\Exceptions\CanNotDeductWorkShiftException;

/**
 * Class CheckInRequestHandler.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CheckInRequestHandler
{
    /**
     * @param CheckInRequest   $request
     * @param LogCheckInAction $logCheckInAction
     */
    public function __invoke(
        CheckInRequest $request,
        LogCheckInAction $logCheckInAction,
        NoveltyTypeRepositoryInterface $noveltyTypeRepository
    ) {
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
                'title' => 'Ya se registra una entrada.',
                'detail' => "Ya se ha registrado entrada en {$exception->checkedInAt}.",
            ]);
        } catch (TooEarlyToCheckException $exception) {
            array_push($errors, [
                'code' => $exception->getCode(),
                'title' => 'Es temprano para registrar la entrada.',
                'detail' => 'Si se llega temprano al turno, se debe registrar una novedad.',
                'meta' => [
                    'novelty_types' => NoveltyTypeResource::collection($noveltyTypeRepository->findForTimeAddition()),
                ],
            ]);
        } catch (TooLateToCheckException $exception) {
            array_push($errors, [
                'code' => $exception->getCode(),
                'title' => 'Es tarde para registrar la entrada.',
                'detail' => 'Si se llega tarde al turno, se debe registrar una novedad.',
                'meta' => [
                    'novelty_types' => NoveltyTypeResource::collection($noveltyTypeRepository->findForTimeSubtraction()),
                ],
            ]);
        } catch (CanNotDeductWorkShiftException $exception) {
            array_push($errors, [
                'code' => $exception->getCode(),
                'title' => 'No fue posible deducir el turno.',
                'detail' => 'No se pudo deducir el turno, se debe elegir uno '
                ."de {$exception->posibleWorkShifts->count()} posibles.",
                'meta' => [
                    'work_shifts' => WorkShiftResource::collection($exception->posibleWorkShifts),
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
                            ? $noveltyTypeRepository->findForTimeSubtraction()
                            : $noveltyTypeRepository->findForTimeAddition()
                    ),
                ],
            ]);
        }

        if ($errors) {
            throw new HttpResponseException(response()->json([
                'message' => 'Error registrando entrada!',
                'errors' => $errors,
            ], Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        event(new CheckedInEvent($timeClockLog->id));

        return new TimeClockLogResource($timeClockLog);
    }
}
