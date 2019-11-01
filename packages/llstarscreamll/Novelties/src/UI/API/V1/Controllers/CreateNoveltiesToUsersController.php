<?php

namespace llstarscreamll\Novelties\UI\API\V1\Controllers;

use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Exceptions\HttpResponseException;
use llstarscreamll\Novelties\Actions\CreateNoveltiesToUsersAction;
use llstarscreamll\Novelties\UI\API\V1\Requests\CreateNoveltiesToUsersRequest;

/**
 * Class CreateNoveltiesToUsersController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateNoveltiesToUsersController
{
    /**
     * @param CreateNoveltiesToUsersRequest $request
     * @param CreateNoveltiesToUsersAction  $action
     */
    public function __invoke(CreateNoveltiesToUsersRequest $request, CreateNoveltiesToUsersAction $action)
    {
        try {
            DB::transaction(function () use ($request, $action) {
                $action->run($request->validated());
            });
        } catch (\Throwable $th) {
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
