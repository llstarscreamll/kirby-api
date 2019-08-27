<?php

namespace llstarscreamll\Novelties\Actions;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use llstarscreamll\Novelties\Contracts\NoveltyRepositoryInterface;

/**
 * Class CreateNoveltiesToUsersAction.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class CreateNoveltiesToUsersAction
{
    /**
     * @var NoveltyRepositoryInterface
     */
    private $noveltyRepository;

    /**
     * @param NoveltyRepositoryInterface $noveltyRepository
     */
    public function __construct(NoveltyRepositoryInterface $noveltyRepository)
    {
        $this->noveltyRepository = $noveltyRepository;
    }

    /**
     * @param array $data
     */
    public function run(array $data): bool
    {
        $currentDate = Carbon::now();
        $employeeIds = new Collection($data['employee_ids']);
        $data = (new Collection($data['novelties']))->map(function ($novelty) use ($employeeIds, $currentDate) {
            $start = Carbon::parse($novelty['start_at']);
            $end = Carbon::parse($novelty['end_at']);

            $novelty['start_at'] = $start;
            $novelty['end_at'] = $end;
            $novelty['total_time_in_minutes'] = $start->diffInMinutes($end);
            $novelty['created_at'] = $currentDate;
            $novelty['updated_at'] = $currentDate;

            return $employeeIds->map(function ($employeeId) use ($novelty) {
                return $novelty + ['employee_id' => $employeeId];
            });
        })->collapse()->chunk(500)->map(function (Collection $chunk) {
            return $this->noveltyRepository->insert($chunk->all());
        });

        return true;
    }
}
