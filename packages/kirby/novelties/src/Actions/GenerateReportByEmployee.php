<?php

namespace Kirby\Novelties\Actions;

use Carbon\Carbon;
use Kirby\Novelties\Contracts\NoveltyRepositoryInterface;

/**
 * Class GenerateReportByEmployee.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class GenerateReportByEmployee
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
     * @param int    $employeeId
     * @param Carbon $startDate
     * @param Carbon $endDate
     */
    public function run(int $employeeId, Carbon $startDate, Carbon $endDate)
    {
        $novelties = $this->noveltyRepository
            ->with(['employee', 'subCostCenter', 'noveltyType', 'approvals'])
            ->findByEmployeeId($employeeId)
            ->findWhereBetween('scheduled_start_at', [
                $startDate->startOfDay()->toDateTimeString(),
                $endDate->endOfDay()->toDateTimeString(),
            ]);

        return $novelties
            ->groupBy(fn ($novelty) => $novelty->scheduled_start_at->toDateString())
            ->map(function ($novelties, $date) {
                return [
                    'date' => $date,
                    'employee' => optional($novelties->first())->employee->toArray(),
                    'novelties' => $novelties->toArray(),
                ];
            })->sortByDesc('date')->values()->all();
    }
}
