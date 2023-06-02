<?php

namespace Kirby\TruckScale\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Kirby\TruckScale\Notifications\ExportWeighingsReady;
use Kirby\Users\Models\User;
use League\Csv\Writer;

class ExportWeighingsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $userID;
    public array $filters = [];

    public function __construct(array $filters, int $userID)
    {
        $this->filters = $filters;
        $this->userID = $userID;
    }

    public function handle()
    {
        $fileName = 'registros_de_pesaje_'.now()->format('Ymd_His').'.csv';
        $writer = Writer::createFromStream($file = tmpfile());
        $writer->setDelimiter(';');
        $writer->insertOne([
            'ID', 'tipo de pesaje', 'placa', 'tipo de vehículo', '# documento conductor', 'nombres conductor', 'peso tara', 'peso bruto', 'descripción', 'estado', 'fecha de creación',
        ]);

        DB::table('weighings')
            ->when(Arr::get($this->filters, 'vehicle_plate'), fn ($q, $v) => $q->where('vehicle_plate', $v))
            ->when(Arr::get($this->filters, 'vehicle_type'), fn ($q, $v) => $q->where('vehicle_type', $v))
            ->when(Arr::get($this->filters, 'status'), fn ($q, $v) => $q->where('status', $v))
            ->when(Arr::get($this->filters, 'date'), fn ($q, $v) => $q->whereBetween('created_at', [Carbon::parse($v)->startOfDay(), Carbon::parse($v)->endOfDay()]))
            ->select([
                'id', 'weighing_type', 'vehicle_plate', 'vehicle_type', 'driver_dni_number', 'driver_name', 'tare_weight', 'gross_weight', 'weighing_description', 'status', 'created_at',
            ])
            ->orderBy('id', 'desc')
            ->chunk(5000, function ($data) use ($writer) {
                $writer->insertAll($data->map(fn ($v) => $this->mapRow($v))->all());
            });

        Storage::disk('private')->put("weighings/exports/{$fileName}", $file);
        $fileUrl = Storage::disk('private')->url("weighings/exports/{$fileName}", now()->addMinutes(60));
        User::find($this->userID)->notify(new ExportWeighingsReady($fileUrl));
    }

    private function mapRow($row)
    {
        return [
            'id' => $row->id,
            'weighing_type' => trans("truck-scale.weighing_types.{$row->weighing_type}"),
            'vehicle_plate' => $row->vehicle_plate,
            'vehicle_type' => $row->vehicle_type,
            'driver_dni_number' => $row->driver_dni_number,
            'driver_name' => $row->driver_name,
            'tare_weight' => $row->tare_weight,
            'gross_weight' => $row->gross_weight,
            'weighing_description' => $row->weighing_description,
            'status' => trans("truck-scale.statuses.{$row->status}"),
            'created_at' => $row->created_at,
        ];
    }
}
