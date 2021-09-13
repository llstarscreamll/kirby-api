<?php

namespace Kirby\Novelties\UI\API\V1\Controllers;

use Carbon\Carbon;
use Kirby\Novelties\Contracts\NoveltyRepositoryInterface;
use Kirby\Novelties\Facades\Novelties;
use Kirby\Novelties\UI\API\V1\Requests\CreateBalanceNoveltyRequest;
use Kirby\Novelties\UI\API\V1\Resources\NoveltyResource;

/**
 * Class CreateBalanceNoveltyController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateBalanceNoveltyController
{
    /**
     * @param  CreateBalanceNoveltyRequest  $request
     * @param  NoveltyRepositoryInterface  $noveltyRepository
     */
    public function __invoke(CreateBalanceNoveltyRequest $request, NoveltyRepositoryInterface $noveltyRepository)
    {
        $hours = floatval($request->time);
        $noveltyTypeId = $hours > 0
            ? Novelties::defaultSubTractNoveltyTypeId()
            : Novelties::defaultAdditionNoveltyTypeId();

        $novelty = $noveltyRepository->create([
            'employee_id' => $request->employee_id,
            'novelty_type_id' => $noveltyTypeId,
            'start_at' => $start = Carbon::parse($request->start_date),
            'end_at' => $start->copy()->addSeconds(abs($hours * 60 * 60)),
            'comment' => $request->comment,
        ]);

        return NoveltyResource::make($novelty);
    }
}
