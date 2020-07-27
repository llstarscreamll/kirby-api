<?php

namespace Kirby\Novelties\UI\API\V1\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Kirby\Employees\Contracts\EmployeeRepositoryInterface;
use Kirby\Employees\UI\API\V1\Resources\EmployeeResource;
use Kirby\Novelties\Contracts\NoveltyTypeRepositoryInterface;
use Kirby\Novelties\UI\API\V1\Requests\NoveltyTypesResumeByEmployeeRequest;
use Prettus\Repository\Criteria\RequestCriteria;

/**
 * Class EmployeeNoveltyTypesRecordsController.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class EmployeeNoveltyTypesRecordsController
{
    /**
     * @var \Kirby\Employees\Contracts\EmployeeRepositoryInterface
     */
    private $employeeRepository;

    /**
     * @var \Kirby\Novelties\Contracts\NoveltyTypeRepositoryInterface
     */
    private $noveltyTypeRepository;

    /**
     * @param EmployeeRepositoryInterface    $employeeRepository
     * @param NoveltyTypeRepositoryInterface $noveltyTypeRepository
     */
    public function __construct(
        EmployeeRepositoryInterface $employeeRepository,
        NoveltyTypeRepositoryInterface $noveltyTypeRepository
    ) {
        $this->employeeRepository = $employeeRepository;
        $this->noveltyTypeRepository = $noveltyTypeRepository;
    }

    /**
     * @param NoveltyTypesResumeByEmployeeRequest $request
     */
    public function __invoke(NoveltyTypesResumeByEmployeeRequest $request)
    {
        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();

        $paginatedEmployees = $this->employeeRepository
            ->pushCriteria(app(RequestCriteria::class))
            ->paginate(min($request->limit, 100), ['id']);

        $noveltiesGroupedByType = $this->noveltyTypeRepository
            ->with(['novelties' => fn($query) => $query
                    ->whereIn('employee_id', $paginatedEmployees->pluck('id'))
                    ->whereBetween('start_at', [$startDate->toDateTimeString(), $endDate->toDateTimeString()])
                    ->select(['id', 'novelty_type_id', 'employee_id', 'start_at', 'end_at']),
            ])->findWhere(['keep_in_report' => true], ['id', 'code', 'name', 'operator']);

        $paginatedEmployees
            ->getCollection()
            ->transform(fn($employee) => $employee->setRelation('noveltyTypes', $this->mapNovelties($noveltiesGroupedByType, $employee)));

        return EmployeeResource::collection($paginatedEmployees);
    }

    /**
     * @param  Collection   $noveltyTypes
     * @param  Employee     $employee
     * @return Collection
     */
    private function mapNovelties($noveltyTypes, $employee): Collection
    {
        return $noveltyTypes->map(function ($noveltyType) use ($employee) {
            $mappedNoveltyType = clone $noveltyType;
            $mappedNoveltyType->setRelation('novelties', $mappedNoveltyType
                    ->novelties->filter(fn($novelty) => $novelty->employee_id === $employee->id));

            return $mappedNoveltyType;
        });
    }
}
