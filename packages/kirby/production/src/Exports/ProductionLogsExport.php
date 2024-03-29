<?php

namespace Kirby\Production\Exports;

use Carbon\Carbon;
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
    public $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * @return Illuminate\Database\Query\Builder
     */
    public function query()
    {
        $machineId = Arr::get($this->params, 'machine_id');
        $productId = Arr::get($this->params, 'product_id');
        $netWeight = Arr::get($this->params, 'net_weight');
        $employeeId = Arr::get($this->params, 'employee_id');
        $startDate = Arr::get($this->params, 'tag_updated_at.start');
        $endDate = Arr::get($this->params, 'tag_updated_at.end');
        $startDate = empty($startDate) ? null : Carbon::parse($startDate);
        $endDate = empty($endDate) ? null : Carbon::parse($endDate);

        return ProductionLog::when(array_filter([$startDate, $endDate]), fn ($q, $dateRange) => $q->whereBetween('tag_updated_at', $dateRange))
            ->when($machineId, fn ($q, $machineId) => $q->where('machine_id', $machineId))
            ->when($productId, fn ($q, $productId) => $q->where('product_id', $productId))
            ->when($employeeId, fn ($q, $employeeId) => $q->where('employee_id', $employeeId))
            // the (? + 0.0) is a hack to make this query compatible with sqlite, see:
            //https://github.com/laravel/framework/issues/31201#issuecomment-615682788
            ->when($netWeight, fn ($q, $netWeight) => $q->whereRaw('gross_weight - tare_weight = (? + 0.0)', [$netWeight]))
            ->with([
                'employee', 'machine', 'product', 'customer',
            ]);
    }

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
            'Peso Neto (kg)',
            'Etiqueta',
            'Destino',
            'Fecha de actualización de etiqueta',
            'Fecha de creación',
        ];
    }

    /**
     * @param  \Kirby\Production\Models\ProductionLog  $log
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
            $log->product->internal_code,
            $log->batch,
            $log->tare_weight,
            $log->gross_weight,
            $log->netWeight(),
            trans($log->tag),
            trans($log->purpose),
            $log->tag_updated_at,
            $log->created_at->toIsoString(),
        ];
    }
}
