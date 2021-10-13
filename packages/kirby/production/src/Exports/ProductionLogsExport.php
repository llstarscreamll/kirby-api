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
        $machineId = Arr::get($this->params, 'machine_id');
        $productId = Arr::get($this->params, 'product_id');
        $netWeight = Arr::get($this->params, 'net_weight');
        $employeeId = Arr::get($this->params, 'employee_id');
        $creationDate = Arr::get($this->params, 'creation_date');
        $creationDate = empty($creationDate) ? null : Carbon::parse($creationDate);

        return ProductionLog::when($creationDate, fn ($q, $creationDate) => $q->whereBetween('created_at', [
            $creationDate->copy()->startOfDay(), $creationDate->copy()->endOfDay(),
        ]))
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
            'Peso Neto (kg)',
            'Fecha',
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
            $log->created_at->toIsoString(),
        ];
    }
}
