<?php

namespace Kirby\Novelties\UI\API\V1\Controllers;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Kirby\Novelties\Actions\CreateManyNoveltiesAction;
use Kirby\Novelties\UI\API\V1\Requests\CreateManyNoveltiesRequest;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CreateManyNoveltiesController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateManyNoveltiesController
{
    /**
     * @param CreateManyNoveltiesRequest $request
     * @param CreateManyNoveltiesAction  $action
     */
    public function __invoke(CreateManyNoveltiesRequest $request, CreateManyNoveltiesAction $action)
    {
        try {
            DB::transaction(fn() => $action->run($request->validated() + ['approvers' => [$request->user()->id]]));
        } catch (\Throwable $th) {
            dd($th);
            throw new HttpResponseException(response()->json([
                'message' => 'Ocurrió un error inesperado al procesar la solicitud',
                'errors' => [[
                    'code' => $th->getCode(),
                    'title' => 'Error inesperado.',
                    'detail' => 'Ha ocurrido un error inesperado procesando la solicitud.',
                ]],
            ], Response::HTTP_EXPECTATION_FAILED));
        }

        return response()->json(['data' => 'ok'], Response::HTTP_CREATED);
    }
}
