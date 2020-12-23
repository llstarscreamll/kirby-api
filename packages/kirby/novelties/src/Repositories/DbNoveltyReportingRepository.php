<?php

namespace Kirby\Novelties\Repositories;

use Illuminate\Support\Facades\DB;
use Kirby\Novelties\Contracts\NoveltyReportingRepository;
use Kirby\Novelties\DTOs\SearchEmployeeNoveltiesData;
use Kirby\Novelties\Models\EmployeeNoveltiesResumeByTypeReport;
use Kirby\Novelties\Models\NoveltyResume;

/**
 * Class DbNoveltyReportingRepository.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class DbNoveltyReportingRepository implements NoveltyReportingRepository
{
    public function employeesResumeByNoveltyTypeChunk(SearchEmployeeNoveltiesData $data, int $chunkSize = 1000, callable $callback)
    {
        DB::table('employees')->select([
            'employees.id', 'employees.code', 'employees.identification_number', 'users.first_name', 'users.last_name',
        ])
            ->join('users', 'users.id', 'employees.id')
            ->when($data->employeeId, fn ($query, $employeeId) => $query->where('employees.id', $employeeId))
            ->orderBy('users.first_name')
            ->chunk(1, fn ($chunk) => $callback($chunk->map(function ($employee) use ($data) {
                $novelties = DB::table('novelties')
                    ->join('novelty_types', 'novelty_types.id', 'novelties.novelty_type_id')
                    ->where(['employee_id' => $employee->id, 'novelty_types.keep_in_report' => true, 'novelties.deleted_at' => null])
                    ->whereBetween('start_at', [$data->startDate, $data->endDate])
                    ->get(['novelties.id', 'novelties.novelty_type_id', 'novelty_types.code', 'novelty_types.operator', 'novelties.start_at', 'novelties.end_at', 'novelties.deleted_at'])
                    ->map(fn ($novelty) => new NoveltyResume($novelty->novelty_type_id, $novelty->operator, $novelty->start_at, $novelty->end_at));

                return new EmployeeNoveltiesResumeByTypeReport(
                    $employee->id,
                    $employee->code,
                    $employee->identification_number,
                    $employee->first_name,
                    $employee->last_name,
                    $novelties
                );
            })));
    }
}
