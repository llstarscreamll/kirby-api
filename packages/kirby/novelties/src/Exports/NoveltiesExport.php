<?php

namespace Kirby\Novelties\Exports;

use Illuminate\Support\Arr;
use Kirby\Novelties\Models\Novelty;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Class NoveltiesExport.
 *
 * @author Johan Alvarez <llstarscreamll@hotmail.com>
 */
class NoveltiesExport implements FromQuery, WithMapping, WithHeadings
{
    /**
     * @var array
     */
    private $params;

    /**
     * @param  array  $params
     */
    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * @return Illuminate\Database\Query\Builder
     */
    public function query()
    {
        $employeeId = Arr::get($this->params, 'employee_id');
        $startDate = Arr::get($this->params, 'time_clock_log_check_out_start_date');
        $endDate = Arr::get($this->params, 'time_clock_log_check_out_end_date');

        $novelties = Novelty::whereBetween(
            'start_at',
            [$startDate, $endDate]
        );

        $employeeId && $novelties->where('employee_id', $employeeId);

        return $novelties->with([
            'employee', 'noveltyType', 'subCostCenter.costCenter', 'approvals',
        ]);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Código empleado',
            'Identificación empelado',
            'Nombres empleado',
            'Apellidos empleado',
            'Fecha inicio',
            'Fecha fin',
            'Código centro de costo',
            'Código sub-centro de costo',
            'Código tipo de novedad',
            'Tiempo en horas',
            'Comentario',
            'Aprobadores',
            'Fecha de creación',
        ];
    }

    /**
     * @param  Novelty  $novelty
     */
    public function map($novelty): array
    {
        return [
            $novelty->employee->code,
            $novelty->employee->identification_number,
            $novelty->employee->user->first_name,
            $novelty->employee->user->last_name,
            optional($novelty->start_at)->toIsoString(),
            optional($novelty->end_at)->toIsoString(),
            $novelty->subCostCenter ? $novelty->subCostCenter->costCenter->code : '',
            optional($novelty->subCostCenter)->code,
            $novelty->noveltyType->code,
            $novelty->total_time_in_hours,
            $novelty->comment,
            $novelty->approvals->map->name->join("\n"),
            $novelty->created_at->toIsoString(),
        ];
    }
}
