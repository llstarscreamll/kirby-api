<?php

namespace Kirby\Novelties\UI\API\V1\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Arr;
use Kirby\Novelties\Contracts\NoveltyRepositoryInterface;
use Kirby\Novelties\UI\API\V1\Requests\DeleteNoveltiesApprovalsByEmployeeAndDateRangeRequest;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DeleteNoveltiesApprovalsByEmployeeAndDateRangeController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class DeleteNoveltiesApprovalsByEmployeeAndDateRangeController
{
    /**
     * @param DeleteNoveltiesApprovalsByEmployeeAndDateRangeRequest $request
     * @param NoveltyRepositoryInterface                            $noveltyRepository
     */
    public function __invoke(DeleteNoveltiesApprovalsByEmployeeAndDateRangeRequest $request, NoveltyRepositoryInterface $noveltyRepository)
    {
        try {
            $employeeId = Arr::get($request->validated(), 'employee_id');
            $endDate = Carbon::parse(Arr::get($request->validated(), 'end_date'));
            $startDate = Carbon::parse(Arr::get($request->validated(), 'start_date'));

            $novelties = $noveltyRepository->whereScheduledForEmployee($employeeId, 'start_at', $startDate, $endDate);
            $noveltyRepository->deleteApprovals($novelties->pluck('id')->all(), $request->user()->id);
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

        return response()->json(['data' => 'ok'], Response::HTTP_OK);
    }
}
