<?php

namespace llstarscreamll\Novelties\Actions;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use llstarscreamll\Novelties\Contracts\NoveltyRepositoryInterface;
use llstarscreamll\Novelties\Contracts\NoveltyTypeRepositoryInterface;
use llstarscreamll\Novelties\Enums\NoveltyTypeOperator;

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
     * @var NoveltyTypeRepositoryInterface
     */
    private $noveltyTypeRepository;

    /**
     * @param NoveltyRepositoryInterface     $noveltyRepository
     * @param NoveltyTypeRepositoryInterface $noveltyTypeRepository
     */
    public function __construct(NoveltyRepositoryInterface $noveltyRepository, NoveltyTypeRepositoryInterface $noveltyTypeRepository)
    {
        $this->noveltyRepository = $noveltyRepository;
        $this->noveltyTypeRepository = $noveltyTypeRepository;
    }

    /**
     * @param array $data
     */
    public function run(array $data): bool
    {
        $currentDate = Carbon::now();
        $employeeIds = new Collection($data['employee_ids']);
        $noveltyTypes = $this->noveltyTypeRepository->findWhereIn('id', Arr::pluck($data['novelties'], 'novelty_type_id'));

        $data = (new Collection($data['novelties']))->map(function ($novelty) use ($employeeIds, $currentDate, $noveltyTypes) {
            $noveltyType = $noveltyTypes->where('id', $novelty['novelty_type_id'])->first();
            $start = Carbon::parse($novelty['scheduled_start_at']);
            $end = Carbon::parse($novelty['scheduled_end_at']);
            $diff = $noveltyType->operator->is(NoveltyTypeOperator::Addition) ? $start->diffInMinutes($end) : $start->diffInMinutes($end) * -1;

            $novelty['scheduled_start_at'] = $start;
            $novelty['scheduled_end_at'] = $end;
            $novelty['total_time_in_minutes'] = $diff;
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
