<?php

namespace Kirby\Production\Exports;

use Illuminate\Support\Arr;
use Kirby\Production\Models\ProductionLog;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductionLogsExport implements FromQuery, WithMapping, WithHeadings
{
    /**
     * @var array
     */
    private $params;

    /**
     * @param array $params
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
        $startDate = Arr::get($this->params, 'from', now()->startOfMonth()->startOfDay()->toISOString());
        $endDate = Arr::get($this->params, 'to', now()->endOfDay()->toISOString());

        $productionLogs = ProductionLog::whereBetween('created_at', [$startDate, $endDate]);

        return $productionLogs->with([
            'employee', 'machine', 'product', 'customer',
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
            'Nombre empleado',
            'Apellido empleado',
            'Nombre Cliente',
            'Nombre Máquina',
            'Código Máquina',
            'Nombre Producto',
            'Código Producto',
            'Lote',
            'Peso Tara (kg)',
            'Peso Bruto (kg)',
            'Fecha',
        ];
    }

    /**
     * @param \Kirby\Production\Models\ProductionLog $log
     */
    public function map($log): array
    {
        return [
            $log->employee->code,
            $log->employee->identification_number,
            $log->employee->user->first_name,
            $log->employee->user->last_name,
            optional($log->customer)->name,
            $log->machine->name,
            $log->machine->code,
            $log->product->name,
            $log->product->code,
            $log->batch,
            $log->tare_weight,
            $log->gross_weight,
            $log->created_at->toIsoString(),
        ];
    }
}
