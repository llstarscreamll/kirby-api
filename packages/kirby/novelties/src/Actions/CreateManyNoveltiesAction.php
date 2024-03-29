<?php

namespace Kirby\Novelties\Actions;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Kirby\Novelties\Contracts\NoveltyRepositoryInterface;

/**
 * Class CreateManyNoveltiesAction.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateManyNoveltiesAction
{
    /**
     * @var NoveltyRepositoryInterface
     */
    private $noveltyRepository;

    public function __construct(NoveltyRepositoryInterface $noveltyRepository)
    {
        $this->noveltyRepository = $noveltyRepository;
    }

    public function run(array $data): bool
    {
        $currentDate = Carbon::now();
        $employeeIds = new Collection($data['employee_ids']);
        $approversIds = $data['approvers'];

        $noveltiesIds = (new Collection($data['novelties']))->map(function ($novelty) use ($employeeIds, $currentDate) {
            $start = Carbon::parse($novelty['start_at']);
            $end = Carbon::parse($novelty['end_at']);

            $novelty['start_at'] = $start;
            $novelty['end_at'] = $end;
            $novelty['created_at'] = $currentDate;
            $novelty['updated_at'] = $currentDate;

            return $employeeIds->map(function ($employeeId) use ($novelty) {
                return $novelty + ['employee_id' => $employeeId];
            });
        })->collapse()->map(fn ($novelty) => $this->noveltyRepository->create($novelty)->id);

        $this->noveltyRepository->attachApproversToNovelties($approversIds, $noveltiesIds->all());

        return true;
    }
}
