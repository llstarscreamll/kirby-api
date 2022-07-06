<?php

namespace Kirby\TimeClock\Exports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TimeClockLogsExport implements FromQuery, WithHeadings, WithMapping
{
    public $params = [];

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * @return Builder
     */
    public function query()
    {
        $employeeIDs = isset($this->params['search']) && Str::contains($this->params['search'], 'employee_id:')
            ? explode(',', explode(':', head(array_filter(explode(';', $this->params['search']), fn ($str) => Str::contains($str, 'employee_id'))))[1])
            : [];

        return DB::table('time_clock_logs')
            ->join('employees', 'time_clock_logs.employee_id', 'employees.id')
            ->join('users', 'users.id', 'employees.id')
            ->leftJoin('sub_cost_centers', 'time_clock_logs.sub_cost_center_id', 'sub_cost_centers.id')
            ->leftJoin('work_shifts', 'time_clock_logs.work_shift_id', 'work_shifts.id')
            ->leftJoin('novelties', 'time_clock_logs.id', 'novelties.time_clock_log_id')
            ->leftJoin('novelty_types', 'novelties.novelty_type_id', 'novelty_types.id')
            ->leftJoin('novelty_approvals', 'novelties.id', 'novelty_approvals.novelty_id')
            ->leftJoin('users AS approvers', 'novelty_approvals.user_id', 'approvers.id')
            ->when(
                isset($this->params['checkedInStart'], $this->params['checkedInEnd']),
                fn ($q) => $q->whereBetween('checked_in_at', [Carbon::parse($this->params['checkedInStart']), Carbon::parse($this->params['checkedInEnd'])])
            )
            ->when($employeeIDs, fn ($q) => $q->whereIn('time_clock_logs.employee_id', $employeeIDs))
            ->select([
                'employees.code AS employeeCode',
                'employees.identification_number AS employeeIdentificationNumber',
                DB::raw('CONCAT(users.first_name, users.last_name) AS employeeFullName'),
                'sub_cost_centers.name AS subCostCenterName',
                'work_shifts.name AS workShiftName',
                DB::raw('CONVERT_TZ(time_clock_logs.checked_in_at, "UTC", "America/Bogota") AS checked_in_at'),
                DB::raw('CONVERT_TZ(time_clock_logs.checked_out_at, "UTC", "America/Bogota") AS checked_out_at'),
                DB::raw('GROUP_CONCAT(CONCAT(novelty_types.code, " ", TIME_TO_SEC(TIMEDIFF(novelties.end_at, novelties.start_at)) / 3600) SEPARATOR "\n") AS novelties'),
                DB::raw('GROUP_CONCAT(CONCAT(approvers.last_name, " - ", CONVERT_TZ(novelty_approvals.created_at, "UTC", "America/Bogota")) SEPARATOR "\n") AS approvals'),
            ])
            ->orderBy('time_clock_logs.id', 'DESC')
            ->groupBy('time_clock_logs.id');
    }

    public function headings(): array
    {
        return [
            'Código de empleado',
            '# indetificación de empleado',
            'Nombres de empleado',
            'Subcentro de costo',
            'Turno',
            'Hora de entrada',
            'Hora de salida',
            'Novedades',
            'Aprobaciones',
        ];
    }

    public function map($row): array
    {
        logger('here');

        return [
            $row->employeeCode,
            $row->employeeIdentificationNumber,
            $row->employeeFullName,
            $row->subCostCenterName,
            $row->workShiftName,
            $row->checked_in_at,
            $row->checked_out_at,
            $row->novelties,
            $row->approvals,
        ];
    }
}
